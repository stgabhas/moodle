<?php
//require(__DIR__.'/init.php');

require('./vendor/solarium/solarium/library/Solarium/Autoloader.php');
Solarium_Autoloader::register();

$config = array(
'endpoint' => array(
    'localhost' => array(
        'host' => '127.0.0.1',
        'port' => 8983,
        'path' => '/solr/',
        )
    )
);

// create a client instance
$client = new Solarium_Client($config);

define('CLI_SCRIPT', true);
require('config.php');

// 348735 forum posts
for ($i = 0; $i < 349000; $i += 2000) {

    $nexti = $i + 2000;
    $sql = "SELECT p.id, d.course, d.forum, p.discussion, p.parent,
                   p.created, p.modified, p.subject, p.message, p.totalscore
              FROM {forum_posts} p
              JOIN {forum_discussions} d
                ON (d.id = p.discussion)
               AND p.id > {$i}
               AND p.id < {$nexti}";
               echo $sql, "\n";
    $results = $DB->get_records_sql($sql);

    $update = $client->createUpdate();

    $docs = array();
    foreach ($results as $k => $r) {

        $doc = $update->createDocument();
        $doc->id =         $r->id;
        $doc->course =     $r->course;
        $doc->forum =      $r->forum;
        $doc->discussion = $r->discussion;
        $doc->parent =     $r->parent;
        $doc->created =    $r->created;
        $doc->modified =   $r->modified;
        $doc->subject =    $r->subject;
        $doc->message =    strip_tags($r->message);
        $doc->totalscore = $r->totalscore;

        $docs[] = $doc;
    }

    $update->addDocuments($docs);
    $update->addCommit();

    try {
        $result = $client->update($update);
        echo 'Update query executed (',$i,')';
        echo 'Query status: ' . $result->getStatus();
        echo 'Query time: ' . $result->getQueryTime();
        echo "\n";
    } catch (Exception $e){
        var_dump($e->getMessage());
    }

}
