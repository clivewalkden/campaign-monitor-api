<?php
/**
 * SOZO Design
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category    SOZO Design
 * @package     Sozo_PACKAGENAME
 * @copyright   Copyright (c) 2018 SOZO Design (https://sozodesign.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *
 */

/**
 * Created by PhpStorm.
 * User: CliveWalkden
 * Date: 16/03/2018
 * Time: 08:54
 */

use \CliveWalkden\CampaignMonitor\CampaignMonitor;
use PHPUnit\Framework\TestCase;

class CampaignMonitorTest extends TestCase
{
    public function setUp()
    {
        $env_file_path = __DIR__ . '/../';

        if (file_exists($env_file_path . '.env')) {
            $dotenv = new Dotenv\Dotenv($env_file_path);
            $dotenv->load();
        }
    }

    public function testInvalidAPIKey()
    {
        $this->expectException('\Exception');
        $CampaignMonitor = new CampaignMonitor('incorrect');
    }

    public function testTestEnvironment()
    {
        $CM_API_KEY = getenv('CM_API_KEY');
        $message = 'No environmental variables! Copy .env.example -> .env and enter your Campaign Monitor account details';
        $this->assertNotEmpty($CM_API_KEY, $message);
    }

    public function testInstantiation()
    {
        $CM_API_KEY = getenv('CM_API_KEY');

        if (!$CM_API_KEY) {
            $this->markTestSkipped('No API Key in ENV');
        }

        $CampaignMonitor = new CampaignMonitor($CM_API_KEY);
        $this->assertInstanceOf('\CliveWalkden\CampaignMonitor\CampaignMonitor', $CampaignMonitor);
    }

    public function testResponseState()
    {
        $CM_API_KEY = getenv('CM_API_KEY');

        if (!$CM_API_KEY) {
            $this->markTestSkipped('No API Key in ENV');
        }

        $CampaignMonitor = new CampaignMonitor($CM_API_KEY);

        $CampaignMonitor->get('/systemdate');

        $this->assertTrue($CampaignMonitor->success());
    }
}
