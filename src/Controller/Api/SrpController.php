<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\Srp;
use App\Entity\User;
use App\Factory\View\Srp\SrpPrepareViewFactory;
use App\Form\Request\Srp\LoginPrepareType;
use App\Form\Request\Srp\LoginType;
use App\Form\Request\Srp\RegistrationType;
use App\Model\Request\LoginRequest;
use App\Model\View\Srp\PreparedSrpView;
use App\Services\SrpHandler;
use App\Services\SrpUserManager;
use Doctrine\ORM\EntityManagerInterface;
use FOS\UserBundle\Model\UserManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;

final class SrpController extends AbstractController
{
    /**
     * @SWG\Tag(name="Srp")
     *
     * @SWG\Parameter(
     *     name="body",
     *     in="body",
     *     @Model(type=\App\Form\Request\Srp\RegistrationType::class)
     * )
     * @SWG\Response(
     *     response=204,
     *     description="Success registration"
     * )
     *
     * @SWG\Response(
     *     response=400,
     *     description="Error in user input",
     *     @SWG\Schema(
     *         type="object",
     *         @SWG\Property(
     *             type="object",
     *             property="errors",
     *             @SWG\Property(
     *                 type="array",
     *                 property="email",
     *                 @SWG\Items(
     *                     type="string",
     *                     example="This value already used"
     *                 )
     *             )
     *         )
     *     )
     * )
     *
     * @Route(
     *     path="/api/srp/registration",
     *     name="api_srp_registration",
     *     methods={"POST"}
     * )
     *
     * @param Request              $request
     * @param UserManagerInterface $manager
     *
     * @return null
     */
    public function registerAction(Request $request, UserManagerInterface $manager)
    {
        $user = new User(new Srp());

        $form = $this->createForm(RegistrationType::class, $user); //TODO email confirmation
        $form->submit($request->request->all());
        if (!$form->isValid()) {
            return $form;
        }

        $manager->updateUser($user);

        return null;
    }

    /**
     * @SWG\Tag(name="Srp")
     *
     * @SWG\Parameter(
     *     name="body",
     *     in="body",
     *     @Model(type=\App\Form\Request\Srp\LoginPrepareType::class)
     * )
     * @SWG\Response(
     *     response=200,
     *     description="Success login prepared",
     *     @Model(type=\App\Model\View\Srp\PreparedSrpView::class)
     * )
     *
     * @SWG\Response(
     *     response=400,
     *     description="Error in user input",
     *     @SWG\Schema(
     *         type="object",
     *         @SWG\Property(
     *             type="object",
     *             property="errors",
     *             @SWG\Property(
     *                 type="array",
     *                 property="email",
     *                 @SWG\Items(
     *                     type="string",
     *                     example="This value already used"
     *                 )
     *             )
     *         )
     *     )
     * )
     *
     * @Route(
     *     path="/api/srp/login_prepare",
     *     name="api_srp_login_prepare",
     *     methods={"POST"}
     * )
     *
     * @param Request                $request
     * @param EntityManagerInterface $entityManager
     * @param SrpHandler             $srpHandler
     * @param SrpPrepareViewFactory  $viewFactory
     *
     * @return PreparedSrpView|FormInterface
     */
    public function prepareLoginAction(Request $request, EntityManagerInterface $entityManager, SrpHandler $srpHandler, SrpPrepareViewFactory $viewFactory)
    {
        $email = $request->request->get('email');
        /** @var User $user */
        $user = $this->getDoctrine()->getRepository(User::class)->findOneBy(['email' => $email]);
        if (null === $user) {
            throw new BadRequestHttpException('No such user');
        }
        $srp = $user->getSrp();

        $form = $this->createForm(LoginPrepareType::class, $srp);
        $form->submit($request->request->all());
        if (!$form->isValid()) {
            return $form;
        }

        $privateEphemeral = $srpHandler->getRandomSeed();
        $publicEphemeralValue = $srpHandler->generatePublicServerEphemeral($privateEphemeral, $srp->getVerifier());
        $srp->setPublicServerEphemeralValue($publicEphemeralValue);
        $srp->setPrivateServerEphemeralValue($privateEphemeral);

        $entityManager->persist($srp);
        $entityManager->flush();

        return $viewFactory->create($srp);
    }

    /**
     * @SWG\Tag(name="Srp")
     *
     * @SWG\Parameter(
     *     name="body",
     *     in="body",
     *     @Model(type=\App\Form\Request\Srp\LoginPrepareType::class)
     * )
     * @SWG\Response(
     *     response=200,
     *     description="Success login prepared",
     *     @SWG\Schema(
     *         type="object",
     *         @SWG\Property(
     *             type="string",
     *             property="secondMatcher",
     *             example="129466c0cc982d254c6712e0a5155b1a7fed06eea59b3d7b4620442e54d38ec2"
     *         ),
     *         @SWG\Property(
     *             type="string",
     *             property="jwt",
     *             example="eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJpYXQiOjE1NDcwMjU4NjAsImV4cCI6MTU0NzExMjI2MCwicm9sZXMiOlsiUk9MRV9VU0VSIl0sInVzZXJuYW1lIjoiaXBvcG92KzZANHh4aS5jb20ifQ.WTL0Wsd6eQieq2xlukeRZvyoRW6dMhoXqFW7JTXia9-VZIHaWoTGPWZytOlY_VOhJ2NGYZ5LI3XD-_wtAmYpgUTutiV5DVl-zGrVmzEKtb2VagNggj0SQeo-4gSgTlrDxZW1iP3VU_FAN8-DCWion7M2WJxhQ1UHZaUALTdLVOOE-AWPWA9-ue5b5XgQ6-noaa6XtaI_vhnyR6C39O0OVV0VgfqXiKuhzknGWr5WrLYgFM1CGso3OIltvY8LZZqlhmBXF5hV7hgTKhPdWUxrMNLmJNAwhSLoWpbZGDuJzRCqC8p5wDv8Q6LSDFK5iG0Vueg5VBecGhVuyhVA9qaVHJRK1amfFweTSQ4RHd3Ly11FsEoUn5yB_sRlFHRyiVswvbjfkVaLusYy8RosOnCm3r8B_FR08ylCtDdEvj56EAag9W3dA1VCG8zcEbUwTqwsnPC97teCUbEseP2qpq-8Wic8DViuRv9z4x5yFrio1R7sCz4-5TI4lmwE002GbN_whd5YHDrRsdu6_9RxR7iuI_9OOdLLe7iVGtWd1RE_NqaOv-ymD5mhKDnZlOjXgsKbhDrvaTZ9s7v4l9H2EmJsTpjNRnaQ6yNmtYMyWR8UQP0cilwoHBSwo3L70kVwfMu0T4WTezd8hgTO6VoX9UG8tLpNcNaoMt6et8Rdsp7uQQU"
     *         )
     *     )
     * )
     *
     * @SWG\Response(
     *     response=400,
     *     description="Error in user input",
     *     @SWG\Schema(
     *         type="object",
     *         @SWG\Property(
     *             type="object",
     *             property="errors",
     *             @SWG\Property(
     *                 type="array",
     *                 property="email",
     *                 @SWG\Items(
     *                     type="string",
     *                     example="This value should not be blank."
     *                 )
     *             )
     *         )
     *     )
     * )
     *
     * @Route(
     *     path="/api/srp/login",
     *     name="api_srp_login",
     *     methods={"POST"}
     * )
     *
     * @param Request                  $request
     * @param SrpUserManager           $srpUserManager
     * @param JWTTokenManagerInterface $jwtManager
     *
     * @return array|FormInterface
     */
    public function loginAction(Request $request, SrpUserManager $srpUserManager, JWTTokenManagerInterface $jwtManager)
    {
        $loginRequest = new LoginRequest();
        $form = $this->createForm(LoginType::class, $loginRequest);
        $form->submit($request->request->all());
        if (!$form->isValid()) {
            return $form;
        }

        $sessionMatcher = $srpUserManager->getMatcherSession($loginRequest);

        if ($sessionMatcher->getMatcher() !== $loginRequest->getMatcher()) {
            throw new BadRequestHttpException('Matchers are not equals');
        }

        $secondMatcher = $srpUserManager->generateSecondMatcher($loginRequest, $sessionMatcher);

        return [
            'secondMatcher' => $secondMatcher,
            'jwt' => $jwtManager->create($loginRequest->getUser()),
        ];
    }

    /**
     * @Route(
     *     path="/srp",
     *     name="srp_form",
     *     methods={"GET"}
     * )
     *
     * @return Response
     */
    public function srpFormAction()
    {
        return $this->render('srp.html.twig');
    }
}
