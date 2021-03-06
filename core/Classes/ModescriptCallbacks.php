<?php

namespace esc\Classes;


use esc\Controllers\HookController;
use esc\Controllers\MapController;
use esc\Models\Player;

class ModescriptCallbacks
{
    static function tmScores($arguments)
    {
        $showScoresHooks = HookController::getHooks('ShowScores');
        HookController::fireHookBatch($showScoresHooks, $arguments);
    }

    static function tmGiveUp($arguments)
    {
        $playerFinishHooks = HookController::getHooks('PlayerFinish');

        $playerLogin = json_decode($arguments[0])->login;
        $player = Player::find($playerLogin);

        HookController::fireHookBatch($playerFinishHooks, $player, 0, "");
    }

    static function tmWayPoint($arguments)
    {
        $playerCheckpointHooks = HookController::getHooks('PlayerCheckpoint');
        $playerFinishHooks = HookController::getHooks('PlayerFinish');

        $wayPoint = json_decode($arguments[0]);

        $player = Player::find($wayPoint->login);
        $map = MapController::getCurrentMap();

        $totalCps = $map->NbCheckpoints;

        //checkpoint passed
        HookController::fireHookBatch($playerCheckpointHooks,
            $player,
            $wayPoint->laptime,
            ceil($wayPoint->checkpointinrace / $totalCps),
            count($wayPoint->curlapcheckpoints) - 1
        );

        //player finished
        if ($wayPoint->isendlap) {
            HookController::fireHookBatch($playerFinishHooks,
                $player,
                $wayPoint->laptime,
                self::cpArrayToString($wayPoint->curlapcheckpoints)
            );
        }
    }

    static function tmStartCountdown($arguments)
    {
        $playerStartCountdown = HookController::getHooks('PlayerStartCountdown');
        $playerLogin = json_decode($arguments[0])->login;
        $player = Player::find($playerLogin);
        HookController::fireHookBatch($playerStartCountdown, $player);
    }

    static function tmStartLine($arguments)
    {
        $playerStartCountdown = HookController::getHooks('PlayerStartLine');
        $playerLogin = json_decode($arguments[0])->login;
        $player = Player::find($playerLogin);
        HookController::fireHookBatch($playerStartCountdown, $player);
    }

    static function tmStunt($arguments)
    {
        //ignore stunts for now
    }

    static function tmPlayerConnect($arguments)
    {
        $playerData = json_decode($arguments[0]);
        $playerConnectHooks = HookController::getHooks('PlayerConnect');

        //string Login, bool IsSpectator
        if (Player::whereLogin($playerData->login)->get()->isEmpty()) {
            $player = Player::create(['Login' => $playerData->login]);
        } else {
            $player = Player::find($playerData->login);
        }

        HookController::fireHookBatch($playerConnectHooks, $player);
    }

    static function tmPlayerLeave($arguments)
    {
        $playerData = json_decode($arguments[0]);
        $playerLeaveHooks = HookController::getHooks('PlayerLeave');

        $player = Player::find($playerData->login);

        HookController::fireHookBatch($playerLeaveHooks, $player);
    }

    /**
     * Convert cp array to comma separated string
     * @param array $cps
     * @return string
     */
    private static function cpArrayToString(array $cps)
    {
        return implode(',', $cps);
    }
}