<?php

namespace Comptes\Bundle\CoreBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class PleinType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('distanceParcourue')
            ->add('quantite')
            ->add('prixLitre')
            ->add('vehicule')
        ;
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Comptes\Bundle\CoreBundle\Entity\Plein'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'comptes_bundle_corebundle_plein';
    }
}
