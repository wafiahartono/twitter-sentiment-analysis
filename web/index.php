<!DOCTYPE html>
<html lang="en">
<head>
    <title>Twitter Sentiment Analysis</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="assets/css/tailwind.output.css"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.3/Chart.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.3/Chart.min.js"></script>
    <link rel="stylesheet" href="assets/css/extensions.css">
</head>
<body>
<h2 class="my-6 text-center text-2xl font-semibold text-gray-700">
    Twitter Sentiment Analysis
</h2>
<form id="form-query" method="post">
    <div class="flex justify-center flex-1">
        <div class="relative w-full max-w-lg focus-within:text-purple-500">
            <div class="absolute inset-y-0 flex items-center pl-2">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                          d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z"
                          clip-rule="evenodd">
                    </path>
                </svg>
            </div>
            <input
                class="w-full pl-8 pr-2 text-sm text-gray-700 placeholder-gray-600 bg-gray-100 border-0 rounded-md:shadow-outline-gray:placeholder-gray-600 focus:placeholder-gray-500 focus:bg-white focus:border-purple-300 focus:outline-none focus:shadow-outline-purple form-input"
                type="text"
                placeholder="Search tweets"
                name="query"
                value="<?= $_POST['query'] ?? '' ?>"/>
        </div>
        <button
            class="ml-2 px-4 py-1 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-purple-600 border border-transparent rounded-md active:bg-purple-600 hover:bg-purple-700 focus:outline-none focus:shadow-outline-purple">
            Search
        </button>
    </div>
    <div class="mt-4 text-center text-sm">
        <span class="font-semibold text-gray-600">Similarity method</span>
        <div class="mt-2">
            <label class="inline-flex items-center text-gray-600">
                <input type="radio"
                       class="text-purple-600 form-radio focus:border-purple-400 focus:outline-none focus:shadow-outline-purple:shadow-outline-gray"
                       name="method"
                       value="cosine">
                <span class="ml-2">Cosine</span>
            </label>
            <label class="inline-flex items-center ml-6 text-gray-600">
                <input type="radio"
                       class="text-purple-600 form-radio focus:border-purple-400 focus:outline-none focus:shadow-outline-purple:shadow-outline-gray"
                       name="method"
                       value="dice">
                <span class="ml-2">Dice</span>
            </label>
            <label class="inline-flex items-center ml-6 text-gray-600">
                <input type="radio"
                       class="text-purple-600 form-radio focus:border-purple-400 focus:outline-none focus:shadow-outline-purple:shadow-outline-gray"
                       name="method"
                       value="jaccard">
                <span class="ml-2">Jaccard</span>
            </label>
        </div>
    </div>
</form>
<?php if (isset($_POST['query'], $_POST['method'])): ?>
    <?php
    require_once __DIR__ . '/../app/app.php';

    // Menginstansiasi objek DistanceMetric sesuai yang dipilih
    if ($_POST['method'] == 'cosine') $distance_metric = new Cosine();
    else if ($_POST['method'] == 'dice') $distance_metric = new Dice();
    else $distance_metric = new Jaccard();

    // Mencari tweet terbaru dengan kata kunci yang dipilih
    $tweets = search_recent_tweets($_POST['query']);

    // Menyimpan teks tweet pada array $tweet_texts
    $tweet_texts = [];
    foreach ($tweets as $tweet) $tweet_texts[] = $tweet['text'];

    // Melakukan prediksi pada kumpulan teks tweet yang didapat
    $prediction_result = predict_sentiment($distance_metric, $tweet_texts);

    // Menyimpan hasil prediksi ke dalam database
    add_new_dataset($tweets, $prediction_result['predictions']);
    ?>
    <h4 class="my-6 text-center text-xl font-semibold text-gray-600">
        <?= empty($tweets) ? 'No tweet found' : 'Tweets and sentiment results' ?>
    </h4>
    <div
        class="<?= empty($tweets) ? 'hidden' : '' ?> w-full max-w-4xl mx-auto mb-8 overflow-hidden rounded-lg shadow-xs">
        <div class="w-full overflow-x-auto">
            <table class="w-full">
                <thead>
                <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-50">
                    <th class="px-4 py-3">User</th>
                    <th class="px-4 py-3">Text</th>
                    <th class="px-4 py-3 text-center">Prediction</th>
                </tr>
                </thead>
                <tbody class="bg-white divide-y">
                <?php foreach ($tweets as $i => $tweet): ?>
                    <tr class="text-gray-700">
                        <td class="px-4 py-3">
                            <div class="flex items-center text-sm">
                                <div class="relative hidden w-8 h-8 mr-3 rounded-full md:block">
                                    <img class="object-cover w-full h-full rounded-full"
                                         src="<?= $tweet['user_image_url'] ?>"
                                         loading="lazy">
                                    <div class="absolute inset-0 rounded-full shadow-inner" aria-hidden="true"></div>
                                </div>
                                <div>
                                    <p class="font-semibold hover:underline">
                                        <?= $tweet['user_username'] ?>
                                    </p>
                                    <p class="text-xs text-gray-600">
                                        <?= $tweet['user_name'] ?>
                                    </p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-sm">
                            <?= $tweet['text'] ?>
                        </td>
                        <?php $prediction = $prediction_result['predictions'][$i]; ?>
                        <td class="px-4 py-3 mx-auto text-xs">
                            <span
                                class="px-2 py-1 font-semibold leading-tight rounded-full prediction-<?= strtolower($prediction) ?>">
                                <?= $prediction ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        </div>
    </div>
    <div
        class="<?= empty($tweets) ? 'hidden' : '' ?> min-w-0 max-w-md mx-auto mb-8 p-4 bg-white rounded-lg shadow-xs dark:bg-gray-800">
        <div class="chartjs-size-monitor">
            <div class="chartjs-size-monitor-expand">
                <div class=""></div>
            </div>
            <div class="chartjs-size-monitor-shrink">
                <div class=""></div>
            </div>
        </div>
        <h4 class="mb-4 font-semibold text-gray-800 dark:text-gray-300">
            Prediction
        </h4>
        <canvas id="chart-prediction" style="display: block; width: 479px; height: 239px;" width="479" height="239"
                class="chartjs-render-monitor"></canvas>
        <div class="flex justify-center mt-4 space-x-3 text-sm text-gray-600 dark:text-gray-400">
            <div class="flex items-center">
                <span class="inline-block w-3 h-3 mr-1 bg-green-600 rounded-full"></span>
                <span>
                    Positive (<?= $prediction_result['count']['positive'] / count($tweets) * 100 ?>%)
                </span>
            </div>
            <div class="flex items-center">
                <span class="inline-block w-3 h-3 mr-1 bg-gray-600 rounded-full"></span>
                <span>
                    Neutral (<?= $prediction_result['count']['neutral'] / count($tweets) * 100 ?>%)
                </span>
            </div>
            <div class="flex items-center">
                <span class="inline-block w-3 h-3 mr-1 bg-red-600 rounded-full"></span>
                <span>
                    Negative (<?= $prediction_result['count']['negative'] / count($tweets) * 100 ?>%)
                </span>
            </div>
        </div>
    </div>
<?php endif ?>
<script type="application/javascript">
    // Mencek radio button metode distance sesuai yang dipilih
    document
        .querySelector('input[name=method][value=<?= $_POST['method'] ?? 'cosine' ?>]')
        .setAttribute('checked', '');
    <?php if (isset($tweets, $prediction_result['count'])): ?>
    // Membuat chart untuk menampilkan jumlah masing-masing label prediksi
    const predictionChartConfig = {
        type: 'pie',
        data: {
            datasets: [
                {
                    data: [
                        <?= $prediction_result['count']['positive'] ?>,
                        <?= $prediction_result['count']['neutral'] ?>,
                        <?= $prediction_result['count']['negative'] ?>
                    ],
                    backgroundColor: ['#057a55', '#4c4f52', '#e02424']
                },
            ],
            labels: ['Positive', 'Neutral', 'Negative'],
        },
        options: {
            legend: {
                display: false
            }
        }
    }
    const predictionChartEl = document.getElementById('chart-prediction');
    window.predictionChart = new Chart(predictionChartEl, predictionChartConfig);
    <?php endif ?>
</script>
</body>
</html>
