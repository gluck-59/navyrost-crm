<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isEdit = $options['is_edit'] ?? false;
        $builder
            ->add('username', TextType::class, [
                'row_attr' => ['class' => 'jopa1'],
                'attr' => ['class' => 'jopa2'],
                'label' => 'Имя',
                ])
            ->add('roles', ChoiceType::class, [
                'row_attr' => ['class' => 'form-check-inline'],
                'label' => 'Роли',
                'mapped' => true,
                'multiple' => true,
                'expanded' => true,
                'choices' => [
                    'Админ' => 'ROLE_ADMIN',
                    'Manager' => 'ROLE_MANAGER',
                ],
                'empty_data' => [],
            ])
            ->add('password', PasswordType::class, [
                'row_attr' => ['class' => 'form-control form-control-sm'],
                'required' => !$isEdit, // пароль обязателен только при регистрации
                'label' => 'Пароль',
                'mapped' => false,
                'attr' => ['autocomplete' => 'new-password'],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter a password',
                    ]),
                    new Length([
                        'min' => 6,
                        'minMessage' => 'Your password should be at least {{ limit }} characters',
                        // max length allowed by Symfony for security reasons
                        'max' => 4096,
                    ]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'is_edit' => false,
        ]);
    }
}
