<?php

declare(strict_types=1);

namespace App\Controller\Api\Team;

use App\Context\ViewFactoryContext;
use App\Controller\AbstractController;
use App\Entity\Team;
use App\Entity\User;
use App\Entity\UserTeam;
use App\Form\Request\Team\AddMemberType;
use App\Form\Request\Team\CreateTeamType;
use App\Form\Request\Team\EditTeamType;
use App\Form\Request\Team\EditUserTeamType;
use App\Model\Request\Team\EditUserTeamRequest;
use App\Model\View\Team\ListView;
use App\Model\View\Team\MemberView;
use App\Model\View\Team\TeamView;
use App\Repository\ItemRepository;
use App\Repository\TeamRepository;
use App\Repository\UserTeamRepository;
use App\Security\Voter\TeamVoter;
use App\Security\Voter\UserTeamVoter;
use App\Services\AdminPromoter;
use App\Services\TeamManager;
use App\Utils\ItemExtractor;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(
 *     path="/api/teams"
 * )
 */
class TeamController extends AbstractController
{
    /**
     * @SWG\Tag(name="Team")
     * @SWG\Parameter(
     *     name="body",
     *     in="body",
     *     @Model(type=\App\Form\Request\Team\CreateTeamType::class)
     * )
     * @SWG\Response(
     *     response=200,
     *     description="Create a team",
     *     @Model(type=\App\Model\View\Team\TeamView::class)
     * )
     * @SWG\Response(
     *     response=401,
     *     description="Unauthorized"
     * )
     *
     * @Route(
     *     name="api_team_create",
     *     methods={"POST"}
     * )
     *
     * @throws \Exception
     *
     * @return TeamView|FormInterface
     */
    public function create(
        Request $request,
        ViewFactoryContext $viewFactoryContext,
        EntityManagerInterface $entityManager,
        TeamManager $teamManager,
        AdminPromoter $adminPromoter
    ) {
        $team = new Team();
        $form = $this->createForm(CreateTeamType::class, $team);
        $form->submit($request->request->all());
        if (!$form->isValid()) {
            return $form;
        }

        $this->denyAccessUnlessGranted(TeamVoter::TEAM_CREATE, $team);

        $entityManager->persist($team);
        $teamManager->addTeamToUser($this->getUser(), UserTeam::USER_ROLE_ADMIN, $team);
        $adminPromoter->addTeamToAdmins($team, $this->getUser());
        $entityManager->flush();

        $teamView = $viewFactoryContext->view($team);

        return $teamView;
    }

    /**
     * @SWG\Tag(name="Team")
     *
     * @SWG\Response(
     *     response=200,
     *     description="A team view",
     *     @Model(type=\App\Model\View\Team\TeamView::class)
     * )
     * @SWG\Response(
     *     response=401,
     *     description="Unauthorized"
     * )
     *
     * @Route(
     *     path="/{team}",
     *     name="api_team_view",
     *     methods={"GET"}
     * )
     *
     * @return TeamView
     */
    public function team(Team $team, ViewFactoryContext $viewFactoryContext)
    {
        $this->denyAccessUnlessGranted(UserTeamVoter::USER_TEAM_VIEW, $team);
        $teamView = $viewFactoryContext->view($team);

        return $teamView;
    }

    /**
     * @SWG\Tag(name="Team")
     *
     * @SWG\Response(
     *     response=200,
     *     description="List of teams",
     *     @SWG\Schema(
     *         type="array",
     *         @Model(type=\App\Model\View\Team\TeamView::class)
     *     )
     * )
     * @SWG\Response(
     *     response=401,
     *     description="Unauthorized"
     * )
     *
     * @Route(
     *     name="api_team_list",
     *     methods={"GET"}
     * )
     *
     * @return TeamView[]
     */
    public function teams(ViewFactoryContext $viewFactoryContext, TeamRepository $teamRepository)
    {
        /** @var User $user */
        $user = $this->getUser();
        if ($user->hasRole(User::ROLE_ADMIN) || $user->hasRole(User::ROLE_SUPER_ADMIN)) {
            $teams = $teamRepository->findAll();
        } else {
            $teams = $teamRepository->findByUser($user);
        }

        return $viewFactoryContext->viewList($teams);
    }

    /**
     * @SWG\Tag(name="Team")
     *
     * @SWG\Parameter(
     *     name="body",
     *     in="body",
     *     @Model(type=\App\Form\Request\Team\CreateTeamType::class)
     * )
     * @SWG\Response(
     *     response=200,
     *     description="Edit a team",
     *     @Model(type=\App\Model\View\Team\TeamView::class)
     * )
     * @SWG\Response(
     *     response=401,
     *     description="Unauthorized"
     * )
     *
     * @Route(
     *     path="/{team}",
     *     name="api_team_edit",
     *     methods={"PATCH"}
     * )
     *
     * @return TeamView
     */
    public function update(Team $team, Request $request, ViewFactoryContext $viewFactoryContext, EntityManagerInterface $entityManager)
    {
        $this->denyAccessUnlessGranted(UserTeamVoter::USER_TEAM_EDIT, $team);

        $form = $this->createForm(EditTeamType::class, $team);
        $form->submit($request->request->all());
        if ($form->isValid()) {
            $entityManager->flush();
        }
        $teamView = $viewFactoryContext->view($team);

        return $teamView;
    }

    /**
     * @SWG\Tag(name="Team")
     *
     * @SWG\Response(
     *     response=200,
     *     description="Delete a team"
     * )
     * @SWG\Response(
     *     response=401,
     *     description="Unauthorized"
     * )
     *
     * @Route(
     *     path="/{team}",
     *     name="api__team_delete",
     *     methods={"DELETE"}
     * )
     *
     * @return JsonResponse
     */
    public function delete(Team $team, EntityManagerInterface $entityManager)
    {
        $this->denyAccessUnlessGranted(TeamVoter::TEAM_CREATE, $team);
        $entityManager->remove($team);
        $entityManager->flush();

        return new JsonResponse([], Response::HTTP_NO_CONTENT);
    }

    /**
     * @SWG\Tag(name="Team")
     *
     * @SWG\Response(
     *     response=200,
     *     description="Default team members",
     *     @SWG\Schema(
     *         type="array",
     *         @Model(type="App\Model\View\Team\MemberView")
     *     )
     * )
     * @SWG\Response(
     *     response=401,
     *     description="Unauthorized"
     * )
     *
     * @Route(
     *     path="/default/members",
     *     methods={"GET"}
     * )
     *
     * @return ListView[]
     */
    public function defaultTeamMembers(UserTeamRepository $userTeamRepository)
    {
        $team = $this->getDefaultTeam();

        $this->denyAccessUnlessGranted(UserTeamVoter::USER_TEAM_VIEW, $team);
        $usersTeams = $userTeamRepository->findMembers($team);

        return MemberView::createMany($usersTeams);
    }

    /**
     * @SWG\Tag(name="Team")
     *
     * @SWG\Response(
     *     response=200,
     *     description="Team members",
     *     @SWG\Schema(
     *         type="array",
     *         @Model(type="App\Model\View\Team\MemberView")
     *     )
     * )
     * @SWG\Response(
     *     response=401,
     *     description="Unauthorized"
     * )
     *
     * @Route(
     *     path="/{team}/members",
     *     methods={"GET"}
     * )
     *
     * @return MemberView[]
     */
    public function members(Request $request, Team $team, UserTeamRepository $userTeamRepository)
    {
        $this->denyAccessUnlessGranted(UserTeamVoter::USER_TEAM_VIEW, $team);
        $ids = $request->query->get('ids', []);
        $usersTeams = $userTeamRepository->findMembers($team, $ids);

        return MemberView::createMany($usersTeams);
    }

    /**
     * @SWG\Tag(name="Team")
     *
     * @SWG\Parameter(
     *     name="body",
     *     in="body",
     *     @Model(type=\App\Form\Request\Team\AddMemberType::class)
     * )
     * @SWG\Response(
     *     response=200,
     *     description="Add team member",
     *     @Model(type="\App\Model\View\Team\MemberView")
     * )
     * @SWG\Response(
     *     response=401,
     *     description="Unauthorized"
     * )
     * @Route(
     *     path="/{team}/members/{user}",
     *     methods={"POST"}
     * )
     *
     * @throws \Exception
     *
     * @return MemberView|FormInterface
     */
    public function addMember(Request $request, Team $team, User $user, EntityManagerInterface $entityManager)
    {
        $this->denyAccessUnlessGranted(UserTeamVoter::USER_TEAM_EDIT, $team);
        $userTeam = new UserTeam();
        $form = $this->createForm(AddMemberType::class, $userTeam);
        $form->submit($request->request->all());

        if (!$form->isValid()) {
            return $form;
        } else {
            $userTeam->setUser($user);
            $userTeam->setTeam($team);
            $entityManager->persist($userTeam);
            $entityManager->flush();
        }

        return MemberView::create($userTeam);
    }

    /**
     * @SWG\Tag(name="Team")
     * @SWG\Response(
     *     response=204,
     *     description="Remove team member"
     * )
     *
     * @Route(
     *     path="/{team}/members/{user}",
     *     methods={"DELETE"}
     * )
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function removeMember(
        Team $team,
        User $user,
        UserTeamRepository $userTeamRepository,
        ItemRepository $itemRepository
    ): JsonResponse {
        if (Team::DEFAULT_GROUP_ALIAS === $team->getAlias()) {
            throw new LogicException('Illegal team');
        }

        $userTeam = $userTeamRepository->findOneByUserAndTeam($user, $team);
        if (!$userTeam instanceof UserTeam) {
            throw new NotFoundHttpException('User Team not found');
        }

        $this->denyAccessUnlessGranted(UserTeamVoter::USER_TEAM_REMOVE_MEMBER, $team);
        $items = ItemExtractor::getTeamItemsForUser($team, $user);
        foreach ($items as $item) {
            $itemRepository->remove($item);
        }
        $itemRepository->flush();
        $userTeamRepository->remove($userTeam);

        return new JsonResponse([], Response::HTTP_NO_CONTENT);
    }

    /**
     * @SWG\Tag(name="Team")
     *
     * @SWG\Parameter(
     *     name="body",
     *     in="body",
     *     @Model(type=\App\Form\Request\Team\EditUserTeamType::class)
     * )
     * @SWG\Response(
     *     response=200,
     *     description="Edit team member",
     *     @Model(type="\App\Model\View\Team\MemberView")
     * )
     * @SWG\Response(
     *     response=401,
     *     description="Unauthorized"
     * )
     *
     * @Route(
     *     path="/{team}/members/{user}",
     *     methods={"PATCH"}
     * )
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     *
     * @return MemberView|FormInterface
     */
    public function editMember(Request $request, Team $team, User $user, UserTeamRepository $userTeamRepository)
    {
        if (Team::DEFAULT_GROUP_ALIAS === $team->getAlias()) {
            throw new LogicException('Illegal team');
        }

        $this->denyAccessUnlessGranted(UserTeamVoter::USER_TEAM_EDIT, $team);

        $userTeam = $userTeamRepository->findOneByUserAndTeam($user, $team);
        if (!$userTeam instanceof UserTeam) {
            throw new NotFoundHttpException('User Team not found');
        }

        $editUserTeamRequest = new EditUserTeamRequest();
        $form = $this->createForm(EditUserTeamType::class, $editUserTeamRequest);
        $form->submit($request->request->all());
        if (!$form->isValid()) {
            return $form;
        }

        $userTeam->setUserRole($editUserTeamRequest->getUserRole());
        $userTeamRepository->save($userTeam);

        return MemberView::create($userTeam);
    }

    /**
     * @SWG\Tag(name="Team")
     *
     * @SWG\Response(
     *     response=200,
     *     description="Team lists",
     *     @SWG\Schema(
     *         type="array",
     *         @Model(type="App\Model\View\Team\ListView")
     *     )
     * )
     * @SWG\Response(
     *     response=401,
     *     description="Unauthorized"
     * )
     * @Route(
     *     path="/{team}/lists",
     *     methods={"GET"}
     * )
     *
     * @return ListView[]
     */
    public function lists(Team $team, ViewFactoryContext $viewFactoryContext)
    {
        $lists = $viewFactoryContext->viewList($team->getLists()->getChildLists()->toArray());
        array_push($lists, $viewFactoryContext->view($team->getTrash()));

        return $lists;
    }
}
