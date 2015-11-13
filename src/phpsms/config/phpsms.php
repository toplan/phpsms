<?php

/*
 * config file for PhpSms
 */
return [

    /*
     * available agents
     * ----------------------------------------------------------
     * 'agentName' => 'options',
     * the options:
     * 1. weight (must be a positive integer)
     * 2. 'backup' (ignore upper/lower case)
     *
     * PS: the greater weight value make the agent is used greater probability,
     *     and it`s default value is '1'.
     * ----------------------------------------------------------
     * supported agents:
     * 'Luosimao', 'YunTongXun', 'YunPian', 'SubMail', 'Ucpaas', 'Log'
     * ----------------------------------------------------------
     * Examples:
     * 'agents' => [
     *      'Luosimao' => '5 backup',
     *      weight is 5, is backup agent.
     *      probability: 5/6
     *
     *      'YunPian'  => 'backup',
     *      weight is 1 (default value), is backup agent.
     *      probability: 1/6
     *
     *      'Log'      => '0 backup'
     *      weight is 0, just a backup agent.
     *      probability: 0, but will used when all agents is run failed.
     * ]
     *
     */
    'agents' => [
        //write you agents here
        'Luosimao' => '5 backup',

    ]

];