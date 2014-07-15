<?php
/**
 * Order exception notification mailer
 *
 * @category Email
 * @package Email\Mail
 * @author Seo Yao
 * @author Andreas Gerhards <andreas@lero9.co.nz>
 * @copyright Copyright (c) 2014 LERO9 Ltd.
 * @license Commercial - All Rights Reserved
 */

namespace Email\Mail;

use Email\Entity\EmailTemplateSection;


class ExceptionNotificationMailer extends AbstractOrderMailer
{
    /** @var array $parameters */
    protected $parameters = array();

    
    /**
     * Set template
     * @param [type] $template [description]
     */
    public function setTemplate($template)
    {
        $this->template = $template;
        return $this;
    }

}