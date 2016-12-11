<?php
require_once 'vendor/autoload.php';

use Dota2Api\Api;

$usedIds = [
];

$userQueue = new SplQueue();


Api::init('181351181CAEEE53E0B358807BC95E4A', array('hostaddress', 'username', 'password', 'databasename', ''));
$startingId = -1;
$startingAccountId = -1;
$counter = 0;
while (true) {
  $matchesMapperWeb = new Dota2Api\Mappers\MatchesMapperWeb();
  $matchesMapperWeb->setMatchesRequested(10);
  if($startingId > 0) {
    $matchesMapperWeb->setStartAtMatchId($startingId);
  }
  if($startingAccountId > 0) {
    $matchesMapperWeb->setAccountId($startingAccountId);
  }
  $matchesShortInfo = $matchesMapperWeb->load();
  echo "Saving new batch of matches for id: $startingAccountId \n";
  $local_counter = 0;
  foreach ($matchesShortInfo as $key=>$matchShortInfo) {
      $matchMapper = new Dota2Api\Mappers\MatchMapperWeb($key);
      $match = $matchMapper->load();
      if ($match) {
        $mm = new Dota2Api\Mappers\MatchMapperDb();
        echo "Saved match: ";
        echo $counter;


        $mm->save($match);

        $slots = $match->getAllSlots();
        foreach($slots as $slot) {
          if(array_key_exists($slot->get("account_id"), $usedIds)) continue;
          $usedIds[$slot->get("account_id")] = true;
          $userQueue->push($slot->get("account_id"));
          $id = $slot->get("account_id");
        }
        $startingId = $match->Get("match_id") - 1;

        $counter = $counter + 1;
        $local_counter = $local_counter + 1;
      }
  }
  if(!$userQueue->isEmpty()) {
    $startingAccountId = $userQueue->pop();
  } else {
    $local_counter = 0;
  }

  if($local_counter <= 0 || $userQueue->count() > 1000) {
    echo "Restarting \n";
    sleep(10);
    $startingId = -1;
    $startingAccountId = -1;
    $userQueue = new SplQueue();
  }
}
