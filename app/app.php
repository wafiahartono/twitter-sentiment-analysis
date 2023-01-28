<?php

// Untuk menampilkan error atau tidak
//mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
error_reporting(E_ERROR | E_PARSE);

// Mematikan batas penggunaan memory PHP
ini_set('memory_limit', '-1');
// Mematikan batas waktu eksekusi
ini_set('max_execution_time', '0');

// Mengimpor file-file yang dibutuhkan
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/distance/Cosine.php';
require_once __DIR__ . '/distance/Dice.php';
require_once __DIR__ . '/distance/Jaccard.php';

use Coderjerk\BirdElephant\BirdElephant;
use Phpml\Classification\KNearestNeighbors;
use Phpml\FeatureExtraction\TfIdfTransformer;
use Phpml\FeatureExtraction\TokenCountVectorizer;
use Phpml\Tokenization\WhitespaceTokenizer;
use Sastrawi\Stemmer\StemmerFactory;
use Sastrawi\StopWordRemover\StopWordRemoverFactory;

// Menampilkan hasil var_dump (mirip dengan print_r) di dalam tag HTML "pre"
function var_dump_pretty($text, $data)
{
    echo '<pre>';
    echo $text . PHP_EOL;
    var_dump($data);
    echo '</pre>';
}

// Fungsi untuk mencari tweet terbaru dengan kata kunci $query
function search_recent_tweets($query)
{
    // Token untuk menggunakan API Twitter
    $api_credentials = [
        'bearer_token' => 'AAAAAAAAAAAAAAAAAAAAAFkRVgEAAAAADnibY6f1rtlaZT2dv32bkgdL1Os%3DcAZFJHqeAuXCRR2aZt4aADOp7YpGzVB3qi6wwlLkLSrG7PDS2w',
        'consumer_key' => '0PitVo3pAzPtYqNWt3vZJ4pGH',
        'consumer_secret' => 'cMyaTn3EetN5sKDwTMKk5HicypQg6cyh9FAZTkgje2OPbr1zJy'
    ];
    // Library untuk mencari tweet
    $lib = new BirdElephant($api_credentials);
    // Parameter pencarian tweet
    $params = [
        // Kata kunci pencarian
        'query' => $query,
        // Jumlah maksimal pencarian
        'max_results' => 50,
        // Data tambahan yang ingin didapatkan. Dalam kasus ini data tambahan yang diminta adalah data user dan data tweet asli (jika tweet berupa retweet)
        'expansions' => 'author_id,referenced_tweets.id.author_id',
        'user.fields' => 'profile_image_url'
    ];
    // Mencari tweet menggunakan library
    $search_result = $lib->tweets()->search()->recent($params);

    // Jika data pada hasil pencarian kosong, maka tidak ada tweet yang ditemukan dan kembalikan array kosongan
    if (!isset($search_result->data)) return [];

    // Memasukkan data tweet pada array $tweets
    $tweets = [];
    foreach ($search_result->data as $tweet) {
        // Teks dari tweet
        $text = $tweet->text;
        // Jika tweet adalah retweet, maka teks $tweet->text tidak utuh. Sehingga harus mendapatkan teks utuhnya pada $search_result->includes->tweets
        if (
            isset($tweet->referenced_tweets) &&
            sizeof($tweet->referenced_tweets) > 0 &&
            $tweet->referenced_tweets[0]->type === 'retweeted'
        ) {
            // Mencari teks retweet pada $search_result->includes->tweets
            foreach ($search_result->includes->tweets as $referenced_tweet) {
                if ($referenced_tweet->id === $tweet->referenced_tweets[0]->id) {
                    $text = $referenced_tweet->text;
                    break;
                }
            }
        }
        // Mengambil data user pada $search_result->includes->users
        $username = '';
        $name = '';
        $user_image_url = '';
        foreach ($search_result->includes->users as $user) {
            if ($tweet->author_id === $user->id) {
                $username = $user->username;
                $name = $user->name;
                $user_image_url = $user->profile_image_url;
                break;
            }
        }
        // Tambahkan data tweet ke array $tweets
        $tweets[] = [
            'id' => (int)$tweet->id,
            'user_username' => $username,
            'user_name' => $name,
            'user_image_url' => $user_image_url,
            'text' => $text
        ];
    }
    return $tweets;
}

// Fungsi untuk mendapatkan objek mysqli
function get_mysqli()
{
    return new mysqli('localhost', 'ubaya', 'ubayapasswordA1!', 'ubaya_iir_fp');
}

// Fungsi untuk melakukan preprocessing pada teks sebelum nantinya dilakukan pembobotan fitur
function transform_text($stemmer, $stopword_remover, $text)
{
    // Menghapus URL yang ada karena tidak relevan pada sentiment analysis
    $result = preg_replace('/https:\/\/t\.co\/[\w\d]+/', '', $text);
    // Mengambil bentuk dasar kata (stemming)
    $result = $stemmer->stem($result);
    // Menghabus stop word
    $result = $stopword_remover->remove($result);
    return $result;
}

// Fungsi untuk memprediksi sentimen dari kumpulan tweet dengan distance metric yang diinginkan
function predict_sentiment($distance_metric, $tweets)
{
    $mysqli = get_mysqli();
    // Mengambil dataset dari database
    $query_result = $mysqli->query('select text, sentiment from tweets');

    $dataset = [];
    $sentiments = [];

    // Menginstansiasi objek Stemmer dan StopWordRemover
    $stemmer = (new StemmerFactory())->createStemmer();
    $stop_word_remover = (new StopWordRemoverFactory())->createStopWordRemover();

    while ($row = $query_result->fetch_assoc()) {
        // Melakukan preprocessing pada teks tweet
        $dataset[] = transform_text($stemmer, $stop_word_remover, $row['text']);
        // Mengubah bentuk label dari float ke string
        if ($row['sentiment'] == 0.0) $sentiments[] = 'Negative';
        else if ($row['sentiment'] == 0.5) $sentiments[] = 'Neutral';
        else $sentiments[] = 'Positive';
    }

    // Menginstansiasi objek pembobotan fitur
    $tf = new TokenCountVectorizer(new WhitespaceTokenizer());
    $tf_idf = new TfIdfTransformer();

    // Memprediksi sentimen setiap tweet dalam loop foreach
    $predictions = [];
    foreach ($tweets as $tweet) {
        $dataset[] = transform_text($stemmer, $stop_word_remover, $tweet);
        $dataset_train = $dataset;

        // Mengekstrak dan melakukan pembobotan fitur menggunakan tf-idf
        $tf->fit($dataset_train);
        $tf->transform($dataset_train);
        $tf_idf->fit($dataset_train);
        $tf_idf->transform($dataset_train);

        $new_data = array_pop($dataset_train);
        // Menentukan nilai k untuk metode k-nearest neigbors
        $k = floor(count($dataset_train) / 3);
        $knn = new KNearestNeighbors($k, $distance_metric);
        // Melatih model knn
        $knn->train($dataset_train, $sentiments);
        // Memprediksi teks tweet dengan model knn
        $predictions[] = $knn->predict($new_data);
    }

    // Menghitung jumlah masing-masing label prediksi sentimen
    $positive_count = 0;
    $neutral_count = 0;
    $negative_count = 0;
    foreach ($predictions as $prediction) {
        if ($prediction == 'Positive') $positive_count++;
        else if ($prediction == 'Neutral') $neutral_count++;
        else $negative_count++;
    }

    return [
        'predictions' => $predictions,
        'count' => [
            'positive' => $positive_count,
            'neutral' => $neutral_count,
            'negative' => $negative_count
        ]
    ];
}

// Fungsi untuk menyimpan dataset baru ke database
function add_new_dataset($tweets, $sentiments)
{
    $mysqli = get_mysqli();
    foreach ($tweets as $i => $tweet) {
        $statement = $mysqli->prepare('insert into tweets (username, text, sentiment) values (?, ?, ?)');
        // Mengubah bentuk label dari string ke float
        if ($sentiments[$i] == 'Positive') $sentiment = 1;
        else if ($sentiments[$i] == 'Neutral') $sentiment = 0.5;
        else $sentiment = 0;
        $statement->bind_param('ssd', $tweet['user_username'], $tweet['text'], $sentiment);
        $statement->execute();
    }
}

// Fungsi untuk melakukan evaluasi dengan distance metric yang diinginkan
function evaluate($distance_metrics)
{
    $mysqli = get_mysqli();
    // Mendapatkan dataset dari database dengan urutan random
    $query_result = $mysqli->query('select text, sentiment from tweets order by rand()');

    // Menentukan offset pembagian data train dengan data test. Offset/index data test dimulai pada 80% jumlah dataset sehingga 80% dataset pertama digunakan untuk data train dan 20% sisanya digunakan untuk data test
    $test_offset = floor($query_result->num_rows * 0.8);

    $dataset = [];
    $sentiments = [];
    $test_tweets = [];

    // Menginstansiasi objek Stemmer dan StopWordRemover
    $stemmer = (new StemmerFactory())->createStemmer();
    $stop_word_remover = (new StopWordRemoverFactory())->createStopWordRemover();

    $i = 0;
    while ($row = $query_result->fetch_assoc()) {
        // Mengubah bentuk label dari float ke string
        if ($row['sentiment'] == 0.0) $sentiment = 'Negative';
        else if ($row['sentiment'] == 0.5) $sentiment = 'Neutral';
        else $sentiment = 'Positive';
        // Jika index loop masih belum mencapai offset data test. Maka data dimasukkan ke data train
        if ($i < $test_offset) {
            $dataset[] = transform_text($stemmer, $stop_word_remover, $row['text']);
            $sentiments[] = $sentiment;
            // Jika telah sampai offset data test maka data dimasukkan ke data test
        } else {
            $test_tweets[] = [
                'text' => $row['text'],
                'sentiment' => $sentiment
            ];
        }
        $i++;
    }

    // Menginstansiasi objek pembobotan fitur
    $tf = new TokenCountVectorizer(new WhitespaceTokenizer());
    $tf_idf = new TfIdfTransformer();

    // Memprediksi sentimen setiap tweet pada data test dalam loop foreach
    foreach ($test_tweets as $i => $tweet) {
        $dataset[] = transform_text($stemmer, $stop_word_remover, $tweet['text']);
        $dataset_train = $dataset;

        // Mengekstrak dan melakukan pembobotan fitur menggunakan tf-idf
        $tf->fit($dataset_train);
        $tf->transform($dataset_train);
        $tf_idf->fit($dataset_train);
        $tf_idf->transform($dataset_train);

        $new_data = array_pop($dataset_train);

        // Menentukan nilai k untuk metode k-nearest neigbors
        $k = floor(count($dataset_train) / 3);

        // Untuk setiap distance metric:
        foreach ($distance_metrics as $distance_metric) {
            // Mendapatkan nama distance metric
            $name = get_class($distance_metric);
            $knn = new KNearestNeighbors($k, $distance_metric);
            // Melatih model knn
            $knn->train($dataset_train, $sentiments);
            // Memprediksi teks tweet dengan model knn
            $prediction = $knn->predict($new_data);
            $test_tweets[$i]['prediction'][$name] = $prediction;
            $test_tweets[$i]['prediction_valid'][$name] = $prediction == $tweet['sentiment'];
        }
    }

    // Instansiasi array hasil evaluasi
    $evaluation_result = [
        'distance_metrics' => [],
        'tweets' => $test_tweets
    ];

    // Menghitung jumlah prediksi valid Untuk setiap distance metric
    foreach ($distance_metrics as $distance_metric) {
        // Mendapatkan nama distance metric
        $name = get_class($distance_metric);
        $valid_count = 0;
        foreach ($test_tweets as $tweet) {
            if ($tweet['prediction_valid'][$name]) $valid_count++;
        }
        $evaluation_result['distance_metrics'][] = $name;
        $evaluation_result['accuracy'][$name] = $valid_count / count($test_tweets) * 100;
        $evaluation_result['valid_count'][$name] = $valid_count;
    }

    return $evaluation_result;
}
