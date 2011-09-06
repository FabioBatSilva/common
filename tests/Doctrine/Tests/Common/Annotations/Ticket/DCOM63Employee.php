<?php

namespace Doctrine\Tests\Common\Annotations\Ticket;

class DCOM63Employee extends DCOM63Person
{
    /**
     * @Doctrine\Tests\Common\Annotations\Fixtures\Annotation\Secure("EMPLOYEE")
     */
    public $salary;
}