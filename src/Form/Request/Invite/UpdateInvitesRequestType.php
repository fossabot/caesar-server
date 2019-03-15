<?php

declare(strict_types=1);

namespace App\Form\Request\Invite;

use App\Entity\Item;
use App\Model\Request\InviteCollectionRequest;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class UpdateInvitesRequestType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('item', EntityType::class, [
                'class' => Item::class,
                'empty_data' => $builder->getData() ? $builder->getData()->getItem()->getId()->toString() : null
            ])
            ->add('invites', CollectionType::class, [
                'constraints' => [
                    new NotBlank(),
                ],
                'allow_add' => true,
                'entry_type' => SecretType::class,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => InviteCollectionRequest::class,
        ]);
    }
}
