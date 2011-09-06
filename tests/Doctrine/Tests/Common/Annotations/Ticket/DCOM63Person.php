<?php

namespace Doctrine\Tests\Common\Annotations\Ticket;

abstract class DCOM63Person
{
    /** @Doctrine\Tests\Common\Annotations\Fixtures\Annotation\Route("/employee") */
    public $route;
}
