<?php
/*
 * @package Email\Entity
 * @author Sean Yao
 * @author Andreas Gerhards <andreas@lero9.co.nz>
 * @copyright Copyright (c) 2014 LERO9 Ltd.
 * @license http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause - Please view LICENSE.md for more information
 */

namespace Email\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * EmailTemplateSection
 *
 * @ORM\Table(name="email_template_section", indexes={@ORM\Index(name="code_idx", columns={"code"})})
 * @ORM\Entity(repositoryClass="Email\Repository\EmailTemplateSectionRepository")
 */
class EmailTemplateSection extends \Magelink\Entity\DoctrineBaseEntity
{

    // Before considering extending this code based approach have a look at the database table email_template_section
    const SECTION_EXCEPTION_NOTIFICATION = 1;
    const SECTION_SHIPPING_NOTIFICATION  = 2;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=45, nullable=false)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="string", length=254, nullable=true)
     */
    private $description;

    /**
     * toString() method
     */
    public function __toString()
    {
        return $this->getName();
    }

    /* get Id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /* set Id
     *
     * @return EmailTemplateSection
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set name
     *
     * @return EmailTemplateSection
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

     /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set name
     *
     * @return EmailTemplateSection
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

}