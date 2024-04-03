<?php

namespace App\State;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\DailyQuest;
use App\Enum\DailyQuestStatusEnum;

class DailyQuestStateProvider implements ProviderInterface
{
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        if ($operation instanceof CollectionOperationInterface) {
            return $this->createQuests();
        }
        return $this->createQuests()[$uriVariables['dayString']] ?? null;
    }

    private function createQuests(int $number = 50): array
    {
        $quests = [];
        for ($i = 0; $i < $number; $i++) {
            $quest = new DailyQuest(new \DateTimeImmutable(sprintf('- %d days', $i)));
            $quest->questName = sprintf('Quest %d', $i);
            $quest->description = sprintf('Description %d', $i);
            $quest->difficultyLevel = $i % 10;
            $quest->status = $i % 2 === 0 ? DailyQuestStatusEnum::ACTIVE : DailyQuestStatusEnum::COMPLETED;
            $quests[$quest->getDayString()] = $quest;
        }
        return count($quests) === 1 ? $quests[array_keys($quests)[0]] : $quests;
    }
}
