<!DOCTYPE html>
<html lang="en">
<head>
    <title>Twitter Sentiment Analysis</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="assets/css/tailwind.output.css"/>
    <link rel="stylesheet" href="assets/css/extensions.css">
</head>
<body>
<h2 class="my-6 text-center text-2xl font-semibold text-gray-700">
    Twitter Sentiment Analysis - Evaluation
</h2>
<?php
require_once __DIR__ . '/../app/app.php';

// Melakukan evaluasi untuk setiap distance metric yang ada
$evaluation_result = evaluate(
    [new Cosine(), new Dice(), new Jaccard()]
);
?>
<h4 class="my-6 text-center text-xl font-semibold text-gray-600">
    Tweets and sentiment results
</h4>
<div class="grid gap-6 max-w-4xl mx-auto mb-8 md:grid-cols-2 xl:grid-cols-4">
    <div class="flex items-center p-4 bg-white rounded-lg shadow-xs dark:bg-gray-800">
        <div>
            <p class="mb-2 text-sm font-medium text-gray-600 dark:text-gray-400">
                Total test data
            </p>
            <p class="text-lg font-semibold text-gray-700 dark:text-gray-200">
                <?= count($evaluation_result['tweets']) ?>
            </p>
        </div>
    </div>
    <?php foreach ($evaluation_result['distance_metrics'] as $name): ?>
        <div class="flex items-center p-4 bg-white rounded-lg shadow-xs dark:bg-gray-800">
            <div>
                <p class="mb-2 text-sm font-medium text-gray-600 dark:text-gray-400">
                    Accuracy (<?= $name ?>)
                </p>
                <p class="text-lg font-semibold text-gray-700 dark:text-gray-200">
                    <?= $evaluation_result['accuracy'][$name] ?>%
                </p>
                <p class="text-sm text-gray-700 dark:text-gray-200">
                    <?= $evaluation_result['valid_count'][$name] ?> valid data
                </p>
            </div>
        </div>
    <?php endforeach ?>
</div>
<div
    class="w-full max-w-4xl mx-auto mb-8 overflow-hidden rounded-lg shadow-xs">
    <div class="w-full overflow-x-auto">
        <table class="w-full">
            <thead>
            <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-50">
                <th class="px-4 py-3">Text</th>
                <th class="px-4 py-3 text-center">Sentiment</th>
                <?php foreach ($evaluation_result['distance_metrics'] as $name): ?>
                    <th class="px-4 py-3 text-center">Prediction (<?= $name ?>)</th>
                <?php endforeach ?>
            </tr>
            </thead>
            <tbody class="bg-white divide-y">
            <?php foreach ($evaluation_result['tweets'] as $tweet): ?>
                <tr class="text-gray-700">
                    <td class="px-4 py-3 text-sm">
                        <?= $tweet['text'] ?>
                    </td>
                    <td class="px-4 py-3 mx-auto text-xs">
                        <span
                            class="px-2 py-1 font-semibold leading-tight rounded-full prediction-<?= strtolower($tweet['sentiment']) ?>">
                            <?= $tweet['sentiment'] ?>
                        </span>
                    </td>
                    <?php foreach ($evaluation_result['distance_metrics'] as $name): ?>
                        <td class="px-4 py-3 mx-auto text-xs">
                            <span
                                class="px-2 py-1 font-semibold leading-tight rounded-full prediction-<?= $tweet['prediction_valid'][$name] ? 'valid' : 'invalid' ?>">
                            <?= $tweet['prediction'][$name] ?>
                            </span>
                        </td>
                    <?php endforeach ?>
                </tr>
            <?php endforeach ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
