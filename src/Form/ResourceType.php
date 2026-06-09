<?php

namespace App\Form;

use App\Entity\Category;
use App\Entity\Ressource;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Form\Extension\Core\Type\FileType;

class ResourceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'label'       => 'Titre de la ressource',
                'attr'        => ['placeholder' => 'Ex : Comment mieux communiquer en couple'],
                'constraints' => [
                    new NotBlank(message: 'Le titre est obligatoire.'),
                    new Length(max: 255, maxMessage: 'Le titre ne peut pas dépasser 255 caractères.'),
                ],
            ])
            ->add('description', TextareaType::class, [
                'label'    => 'Description courte',
                'required' => false,
                'attr'     => ['rows' => 3, 'placeholder' => 'Résumé en quelques phrases...'],
            ])
            ->add('contenu', TextareaType::class, [
                'label'    => 'Contenu complet',
                'required' => false,
                'attr'     => ['rows' => 8, 'placeholder' => 'Développez votre ressource ici...'],
            ])
            ->add('media', FileType::class, [
                'label'      => 'Importer un media (vidéo, audio ou image)',
                'mapped'     => false,
                'required'   => false,
                'constraints' => [
                    new File(
                        maxSize: '50M',
                        maxSizeMessage: 'Le fichier ne doit pas dépasser 50M',
                        mimeTypes: [
                            //type image
                            'image/jpeg',
                            'image/png',
                            'image/gif',
                            'image/bmp',
                            //type video
                            'video/mp4',
                            'video/ogg',
                            'video/webm',
                            'video/avi',
                            //type audio
                            'audio/mpeg',
                            'audio/ogg',
                            'audio/pm3',
                            //type document
                            'application/pdf',
                        ],
                        mimeTypesMessage: 'Please upload a valid image file.',
                    ),
                ],
                'attr'        => [
                    'accept' => 'image/*, video/*, audio/*, */*',
                ],
            ])
            ->add('typeRessource', ChoiceType::class, [
                'label'   => 'Type de ressource',
                'choices' => [
                    'Article'   => 'article',
                    'Vidéo'     => 'video',
                    'Activité'  => 'activite',
                    'Jeu'       => 'jeu',
                    'Podcast'   => 'podcast',
                ],
            ])
            ->add('visibilite', ChoiceType::class, [
                'label'   => 'Visibilité',
                'choices' => [
                    'Publique (soumise à modération)' => 'public',
                    'Partagée (via invitation)'        => 'partage',
                    'Privée (visible uniquement par moi)' => 'prive',
                ],
            ])
            ->add('category', EntityType::class, [
                'label'        => 'Catégorie',
                'class'        => Category::class,
                'choice_label' => 'nom',
                'placeholder'  => '-- Choisir une catégorie --',
                'required'     => false,
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Publier la ressource',
                'attr'  => ['class' => 'btn-submit'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Ressource::class,
        ]);
    }
}
