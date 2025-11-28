<?php

namespace App\Form;

use App\Entity\Customer;
use App\Entity\Equipment;
use App\Entity\Request;
use App\Repository\CustomerRepository;
use App\Repository\EquipmentRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Doctrine\ORM\EntityRepository;

class RequestCreateType extends AbstractType
{
    public function __construct(
        private readonly CustomerRepository $customerRepository,
        private readonly EquipmentRepository $equipmentRepository,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('customer', EntityType::class, [
                'class' => Customer::class,
                'choice_label' => 'name',
                'label' => 'Клиент',
                'placeholder' => 'Клиент',
                'required' => true,
                'query_builder' => function (EntityRepository $repository) use ($options) {
                    $qb = $repository->createQueryBuilder('c')->orderBy('c.name', 'ASC');

                    if (!$options['is_admin'] && isset($options['current_user'])) {
                        $qb->andWhere('c.creator = :creator')->setParameter('creator', $options['current_user']);
                    }

                    return $qb;
                },
                'attr' => [
                    'class' => 'form-select',
                    'data-request-customer' => 'true',
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Необходимо выбрать клиента']),
                ],
            ])
            ->add('name', TextType::class, [
                'label' => 'Название заявки',
                'attr' => [
                    'placeholder' => 'краткое описание заявки',
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Необходимо заполнить краткое описание заявки']),
                    new Length(['max' => 255]),
                ],
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'Подробное  описание заявки',
                'required' => false,
                'attr' => ['rows' => 3],
            ])
        ;

        $this->addEquipmentField($builder, null, $options);

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($options) {
            /** @var Request|null $requestEntity */
            $requestEntity = $event->getData();
            $customer = $requestEntity?->getCustomer();
            $this->addEquipmentField($event->getForm(), $customer, $options);
        });

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) use ($options) {
            $data = $event->getData();
            $customer = null;
            if (!empty($data['customer'])) {
                $customer = $this->customerRepository->find($data['customer']);
            }
            $this->addEquipmentField($event->getForm(), $customer, $options);
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Request::class,
            'current_user' => null,
            'is_admin' => false,
            'equipment_api_url' => null,
        ]);
    }

    private function addEquipmentField(FormInterface|FormBuilderInterface $form, ?Customer $customer, array $options): void
    {
        $equipmentChoices = [];
        $isCustomerAllowed = false;
        if ($customer !== null) {
            $customerCreator = $customer->getCreator();
            if ($options['is_admin'] || ($customerCreator && $options['current_user'] && $customerCreator->getId() === $options['current_user']->getId())) {
                $equipmentChoices = $this->equipmentRepository->findByCustomer($customer);
                $isCustomerAllowed = true;
            }
        }

        $form->add('equipment', EntityType::class, [
            'class' => Equipment::class,
            'choice_label' => 'name',
            'choices' => $equipmentChoices,
            'placeholder' => '',
            'label' => 'Оборудование',
            'required' => true,
            'disabled' => $customer === null || !$isCustomerAllowed,
            'attr' => [
                'class' => 'form-select',
                'data-request-equipment' => 'true',
                'data-equipment-url-template' => $options['equipment_api_url'] ?? '',
                'data-equipment-placeholder' => 'сначала выберите клиента',
                'data-disable-auto-load' => empty($equipmentChoices) ? '1' : '0',
                'data-equipment-loading-text' => 'Загрузка оборудования...',
                'data-equipment-empty-text' => 'Оборудование не найдено',
                'data-equipment-error-text' => 'Ошибка загрузки оборудования',
            ],
            'constraints' => [
                new NotBlank(['message' => 'Необходимо выбрать оборудование']),
            ],
        ]);
    }
}
