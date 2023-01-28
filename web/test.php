<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/app.php';

use Coderjerk\BirdElephant\BirdElephant;

//$api_credentials = [
//    'bearer_token' => 'AAAAAAAAAAAAAAAAAAAAAFkRVgEAAAAADnibY6f1rtlaZT2dv32bkgdL1Os%3DcAZFJHqeAuXCRR2aZt4aADOp7YpGzVB3qi6wwlLkLSrG7PDS2w',
//    'consumer_key' => '0PitVo3pAzPtYqNWt3vZJ4pGH',
//    'consumer_secret' => 'cMyaTn3EetN5sKDwTMKk5HicypQg6cyh9FAZTkgje2OPbr1zJy'
//];
//// Library untuk mencari tweet
//$lib = new BirdElephant($api_credentials);
//// Parameter pencarian tweet
//$params = [
//    // Kata kunci pencarian
//    'query' => $_GET['q'] ?? 'korupsi',
//    // Jumlah maksimal pencarian
//    'max_results' => 10,
//    // Data tambahan yang ingin didapatkan. Dalam kasus ini data tambahan yang diminta adalah data user dan data tweet asli (jika tweet berupa retweet)
//    'expansions' => 'author_id,referenced_tweets.id.author_id',
//    'user.fields' => 'profile_image_url'
//];
//// Mencari tweet menggunakan library
//$search_result = $lib->tweets()->search()->recent($params);

//echo json_encode($search_result);

echo json_encode(search_recent_tweets('korupsi'));