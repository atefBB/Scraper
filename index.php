<?php
/**
 * Todo: Send a random user agent string and sleep a random amount between requests.
 */
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Extract and sanatize input:
    $domain = filter_input(INPUT_POST, 'domain', FILTER_SANITIZE_URL);
    $terms  = filter_input(INPUT_POST, 'terms', FILTER_SANITIZE_STRING);

    require 'vendor/autoload.php';

    // Build up a search URL:
    $pages = 10;
    $url   = 'http://www.google.ca/search?' . http_build_query(array('q' => $terms));

    // Request search results:
    $client  = new Goutte\Client;
    $crawler = $client->request('GET', $url);

    // See response content:
    // $response = $client->getResponse();
    // var_dump($response->getContent());die;

    // Start crawling the search results:
    $page   = 1;
    $result = null;

    while (is_null($result) || $page <= $pages) {
        // If we are moving to another page then click the paging link:
        if ($page > 1) {
            $link    = $crawler->selectLink($page)->link();
            $crawler = $client->click($link);
        }

        // Use a CSS filter to select only the result links:
        $crawler->filter('cite')->each(
            function ($node, $index) use (
                &$result, $domain, &$page
            ) {
                // Search the links for the domain
                if (strstr($node->text(), $domain)) {
                    $result = ($index + 1) + (($page - 1) * 10);
                    return false;
                }
            }
        );

        $page++;
    }
}

// A simple HTML escape function:
function escape($string = '')
{
    return htmlspecialchars($string, ENT_COMPAT, 'UTF-8', false);
}
?>

<!DOCTYPE html>
<head>
	<title>Scrape Google with Goutte</title>
	<meta charset="utf-8" />
</head>
<body>
	<h1>Scrape Google with Goutte: </h1>
	<form action="." method="post" accept-charset="UTF-8">
		<label>Domain: <input type="text" name="domain" value="<?=isset($domain) ? escape($domain) : '';?>" /></label>
		<label>Search Terms: <input type="text" name="terms" value="<?=isset($terms) ? escape($terms) : '';?>" /></label>
		<input type="submit" value="Scrape Google" />
	</form>

	<?php if (isset($domain, $terms, $url, $result, $page)): ?>
		<h1>Scraping Results:</h1>
		<p>Searching Google for <b><?=escape($domain);?></b> using the terms <i>"<?=escape($terms);?>"</i>.</p>
		<p><a href="<?=escape($url);?>" target="_blank">See Actual Search Results</a></p>
		<p>Result Number: <?=escape($result);?></p>
		<p>Page Number: <?=escape($page);?></p>
	<?php endif;?>
</body>
</html>
