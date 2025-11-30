<?php

namespace App\Form;

use App\Entity\Customer;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Doctrine\ORM\EntityRepository;

    class CustomerType extends AbstractType
    {
        public function buildForm(FormBuilderInterface $builder, array $options): void
        {
            $builder
            ->add('name', TextType::class, [
                'label' => 'Название',
                'constraints' => [
                    new NotBlank(['message' => 'Название обязательно']),
                    new Length(['max' => 64]),
                ],
            ])
            ->add('data', TextareaType::class, [
                'label' => 'Доп. данные',
                'required' => false,
                'empty_data' => '',
                'constraints' => [
                    new Length(['max' => 255]),
                ],
                'attr' => ['rows' => 2],
            ])
            ->add('parent', EntityType::class, [
                'class' => Customer::class,
                'choice_label' => 'name',
                'placeholder' => 'Нет',
                'label' => 'Родительский филиал',
                'required' => false,
                'choice_attr' => function (Customer $choice) {
                    $username = $choice->getCreator() ? $choice->getCreator()->getUsername() : '—';
                    return ['data-subtext' => $username];
                },
                'query_builder' => function (EntityRepository $er) use ($options) {
                    $qb = $er->createQueryBuilder('c')->orderBy('c.name', 'ASC');
                    // Фильтруем по создателю для не-админов
                    if (isset($options['is_admin']) && !$options['is_admin']) {
                        if (!isset($options['current_user']) || null === $options['current_user']) {
                            // если по какой-то причине пользователь не передан — возвращаем пустой список
                            $qb->andWhere('1 = 0');
                        } else {
                            $qb->andWhere('c.creator = :creator')->setParameter('creator', $options['current_user']);
                        }
                    }
                    // Исключаем текущего клиента из списка при редактировании (только если у него есть ID)
                    if (
                        isset($options['current_customer'])
                        && $options['current_customer'] instanceof Customer
                        && null !== $options['current_customer']->getId()
                    ) {
                        $qb->andWhere('c != :current')->setParameter('current', $options['current_customer']);
                    }
                    return $qb;
                },
                'attr' => [
                    'class' => 'selectpicker form-select',
                    'data-live-search' => 'true',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Customer::class,
            'current_customer' => null,
            'current_user' => null,
            'is_admin' => false,
        ]);
    }
}
