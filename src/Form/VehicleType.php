<?php

namespace App\Form;

use App\Entity\Vehicle;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Range;

class VehicleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('marque', TextType::class, [
                'label' => 'Marque',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: Toyota',
                ],
            ])
            ->add('model', TextType::class, [
                'label' => 'Modèle',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: Corolla',
                ],
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Type de véhicule',
                'choices' => [
                    'Berline' => 'berline',
                    'SUV' => 'suv',
                    'Coupé' => 'coupe',
                    'Camion' => 'camion',
                ],
                'attr' => [
                    'class' => 'form-select',
                ],
            ])
            ->add('registration', TextType::class, [
                'label' => 'Immatriculation',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: AB-123-CD',
                ],
            ])
            ->add('price_per_day', NumberType::class, [
                'label' => 'Prix par jour (€)',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: 50',
                ],
                'constraints' => [
                    new Range([
                        'min' => 20,
                        'max' => 50,
                        'notInRangeMessage' => 'Le prix doit être compris entre {{ min }} et {{ max }} euros.',
                    ]),
                ],
            ])
            ->add('availability', ChoiceType::class, [
                'label' => 'Disponibilité',
                'choices' => [
                    'Disponible' => true,
                    'Indisponible' => false,
                ],
                'expanded' => true, // Affiche des boutons radio
                'attr' => [
                    'class' => 'form-check',
                ],
            ])
            ->add('image', FileType::class, [
                'label' => 'Image',
                'mapped' => false, // Ne pas lier ce champ à une propriété de l'entité
                'required' => false, // Le champ n'est pas obligatoire
                'attr' => [
                    'class' => 'form-control',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Vehicle::class,
        ]);
    }
}
