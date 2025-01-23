<?php

namespace App\Form;

use App\Entity\Reservation;
use App\Entity\Vehicle;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;

class ReservationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // Date de début
// Date de début
            ->add('startDate', DateTimeType::class, [
                'label' => 'Date de début',
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control flatpickr-start', 
                ],
            ])

            ->add('endDate', DateTimeType::class, [
                'label' => 'Date de fin',
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control flatpickr-end', 
                ],
            ])



            // Prix total (calculé ou modifiable par un administrateur)
            ->add('totalPrice', NumberType::class, [
                'label' => 'Prix total (€)',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Le prix sera calculé automatiquement',
                ],
                'required' => false, // Peut être modifié uniquement par un admin
            ])

            // Statut de la réservation
            ->add('status', ChoiceType::class, [
                'label' => 'Statut de la réservation',
                'choices' => [
                    'En attente' => 'pending',
                    'Confirmée' => 'confirmed',
                    'Annulée' => 'cancelled',
                ],
                'attr' => [
                    'class' => 'form-select',
                ],
            ])

            // Véhicule associé
            ->add('vehicle', EntityType::class, [
                'label' => 'Véhicule',
                'class' => Vehicle::class,
                'choice_label' => function (Vehicle $vehicle) {
                    return $vehicle->getMarque() . ' ' . $vehicle->getModel();
                },
                'attr' => [
                    'class' => 'form-select',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Reservation::class,
        ]);
    }
}
