<?php

namespace App\Form;

use App\Entity\Equipment;
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

class EquipmentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('customer', EntityType::class, [
                'class' => Customer::class,
                'choice_label' => 'name',
                'placeholder' => 'Выберите клиента',
                'label' => 'Клиент',
                'required' => true,
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
                    return $qb;
                },
                'attr' => [
                    'class' => 'selectpicker form-select',
                    'data-live-search' => 'true',
                ],
            ])
            ->add('name', TextType::class, [
                'label' => 'Название',
                'constraints' => [
                    new NotBlank(['message' => 'Название обязательно']),
                    new Length(['max' => 255]),
                ],
            ])
            ->add('mark', TextType::class, [
                'label' => 'Марка',
                'required' => false,
                'constraints' => [
                    new Length(['max' => 255]),
                ],
            ])
            ->add('city', TextType::class, [
                'label' => 'Город',
                'required' => false,
                'constraints' => [
                    new Length(['max' => 255]),
                ],
            ])
            ->add('address', TextType::class, [
                'label' => 'Адрес объекта',
                'required' => false,
                'constraints' => [
                    new Length(['max' => 255]),
                ],
            ])
            ->add('serial', TextType::class, [
                'label' => 'Серийный номер',
                'required' => false,
                'constraints' => [
                    new Length(['max' => 255]),
                ],
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'Примечание',
                'required' => false,
                'empty_data' => '',
                'attr' => ['rows' => 1],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Equipment::class,
            'current_user' => null,
            'is_admin' => false,
        ]);
    }
}
