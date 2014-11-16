<?php

namespace Comptes\Bundle\CoreBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class VehiculeType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('nom')
            ->add('dateAchat')
            ->add('kilometrageAchat')
            ->add('kilometrageInitial')
            ->add('prixAchat')
            ->add('capaciteReservoir')
            ->add('carburant')
        ;
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Comptes\Bundle\CoreBundle\Entity\Vehicule'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'comptes_bundle_corebundle_vehicule';
    }
}
