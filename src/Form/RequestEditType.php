<?php

namespace App\Form;

use App\Entity\Request;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class RequestEditType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', HiddenType::class, [
                'constraints' => [
                    new NotBlank(['message' => 'Необходимо заполнить краткое описание заявки']),
                    new Length(['max' => 255]),
                ],
                'attr' => [
                    'data-request-title-input' => 'true',
                ],
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Статус',
                'choices' => Request::getStatusChoices(),
                'attr' => ['class' => 'form-select'],
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'Подробное описание заявки',
                'required' => false,
                'attr' => ['rows' => 4, 'class' => 'form-control'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Request::class,
        ]);
    }
}
