<?php

namespace App\Tests\Functional;

use App\ApiResource\DailyQuest;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * @method AppKernelBrowser browser(array $options = [], array $server = [])
 */
class DailyQuestResourceTest extends ApiTestCase
{
    use ResetDatabase;

    public function testDailyQuestCollectionReturned(): void
    {
        $json = $this->browser()
            ->get('/api/quests')
            ->assertStatus(200)
            ->assertJsonMatches('length("hydra:member")', 50)
            ->json();

        $this->browser()
            ->get($json->decoded()['hydra:member'][0]['@id'])
            ->assertStatus(Response::HTTP_OK);

    }

    /**
     * @param int $number
     * @return DailyQuest|DailyQuest[]
     */
    private function createDailyQuest (int $number): DailyQuest|array
    {
        $manager = self::getContainer()->get(EntityManagerInterface::class);

        $array = [];
        for ($i = 0; $i < $number; $i++) {
            $quest = new DailyQuest();
            $manager->persist($quest);
            $array[] = $quest;
        }
        $manager->flush();

        return count($array) === 1 ? $array[0] : $array;
    }
}