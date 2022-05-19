<?php
require __DIR__ . "/vendor/autoload.php";

use Goutte\Client;

$short_param_options = "t:";
$long_param_options = ["trademark:"];
$param_options = getopt($short_param_options, $long_param_options);

$client = new Client();
const CSRF_INPUT_SELECTOR = "#basicSearchForm > input[type='hidden'][name='_csrf']";
const SEARCH_PAGE_URL = "https://search.ipaustralia.gov.au/trademarks/search/advanced";
const DATA_PROPERTIES_SELECTORS = [
    'number' => '.number',
    'name' => '.trademark.words',
    'logo' => '.trademark.image > img',
    'classes' => '.classes',
    'status' => 'td.status'
];
if (isset($param_options["tm"]) || isset($param_options["trademark"])) {
    $trademark = isset($param_options["t"]) ? $param_options["t"] : $param_options["trademark"];
    $page = $client->request('GET', SEARCH_PAGE_URL);
    $page_search_form = $page->filter("#basicSearchForm")->form();
    $page_search_form['wv[0]']->setValue($trademark);
    $page_search_form_values = $page_search_form->getPhpValues();
    $page_search_result = $client->submit($page_search_form, $page_search_form_values);
    $page_search_result_tBodies = $page_search_result->filter('#resultsTable > tbody');
    $page_search_result_tBodies_values = $page_search_result_tBodies->each(function ($tb) {
        $value = [];
        $value['number'] = $tb->filter(DATA_PROPERTIES_SELECTORS['number'])->first()->text(null);
        try {
            $value['logo_url'] = $tb->filter(DATA_PROPERTIES_SELECTORS['logo'])->first()->attr('src');
        } catch (Exception $e) {
            $value['logo_url'] = null;
        }
        $value['name'] = $tb->filter(DATA_PROPERTIES_SELECTORS['name'])->first()->text(null);
        $value['classes'] = $tb->filter(DATA_PROPERTIES_SELECTORS['classes'])->first()->text(null);
        $status = $tb->filter(DATA_PROPERTIES_SELECTORS['status'])->first()->text(null);
        $status = str_replace("●", "", $status);
        $status != null ? preg_match("/^\s*([a-zA-Z0-9]+)/", $status, $status1) : $status1 = [null];
        $value['status1'] = $status1[0];
        $value['status2'] = ucwords($status);
        $value['details_page_url'] = "https://search.ipaustralia.gov.au/trademarks/search/view/" . $value['number'];
        return $value;
    });
    $search_result_count = explode(" ", $page_search_result->filter('.pagination-container .pagination-count')->first()->text(count($page_search_result_tBodies_values)));
    $search_result_count_value = $search_result_count[array_key_last($search_result_count)];
    print(print_r(json_encode($page_search_result_tBodies_values, JSON_PRETTY_PRINT), true));
    print("\n\n");
    print(print_r(json_encode(["total" => $search_result_count_value], JSON_PRETTY_PRINT), true));
} else {
    print("trademark is required!\nRun: php index.php --trademark=\"abc\"");
}
