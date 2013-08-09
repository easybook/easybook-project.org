<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Yaml\Yaml;

// -- MIDDLEWARES -------------------------------------------------------------
$app->after(function (Request $request, Response $response) {
    $response->setCache(array(
        'public'   => true,
        'max_age'  => 6 * 3600,
        's_maxage' => 6 * 3600,
    ));
});

// -- DOCUMENTATION -----------------------------------------------------------
$app->get('/documentation/', function () use ($app) {
    return $app->redirect('/documentation/index.html');
});
$app->get('/documentation/index.html', function () use ($app) {
    // do nothing, because this controller never gets executed
    // the web server intercepts it, because the 'index.html'
    // file exists physically in the web/ directory
    // this controller is needed to have the proper named route
})
->bind('documentation');

// -- BLOG --------------------------------------------------------------------
$app->get('/blog/{slug}/', function ($slug) use ($app) {
    $post = getBlogPostBySlug($slug, $app);

    if (null == $post) {
        $app->abort(404, 'The requested blog post does not exist.');
    }

    return $app['twig']->render('blog_show.html.twig', array(
        'post' => $post
    ));
})
->bind('blog_show');

$app->get('/blog/', function () use ($app) {
    $posts = getBlogPosts($app);

    return $app['twig']->render('blog_index.html.twig', array(
        'posts' => $posts
    ));
})
->bind('blog');

// -- DOWNLOAD ----------------------------------------------------------------
$app->get('/download/', function () use ($app) {
    return $app['twig']->render('download.html.twig');
})
->bind('download');

// -- STATIC PAGES ------------------------------------------------------------
$app->get('/about/', function () use ($app) {
    return $app['twig']->render('about.html.twig');
})
->bind('about');

$app->get('/contact/', function () use ($app) {
    return $app['twig']->render('contact.html.twig');
})
->bind('contact');

// -- HOMEPAGE ----------------------------------------------------------------
$app->get('/', function () use ($app) {
    return $app['twig']->render('index.html.twig');
})
->bind('homepage');

// -- ERROR PAGES -------------------------------------------------------------
$app->error(function (\Exception $e, $code) use ($app) {
    if ($app['debug']) {
        return;
    }

    return new Response($app['twig']->render('error.html.twig', array('code' => $code)), $code);
});

///// UTILITIES ///////////////////////////////////////////////////////////////
function getBlogPostBySlug($slug, $app) {
    $posts = getBlogPosts($app);

    return array_key_exists($slug, $posts) ? $posts[$slug] : null;
}

/**
 * It parses the posts information written in a YAML file and
 * converts it to a serialized PHP array. This array is cached in
 * a file. The original YAML informacion is very basic (no slugs, content
 * written in HTML, etc.)
 */
function getBlogPosts($app) {
    $cachedPostsFile = __DIR__.'/../cache/compiled_posts.php';

    if (!file_exists($cachedPostsFile)
        || date('now') - filemtime($cachedPostsFile) > 2 * 3600) {
        // YAML + Markdown = problems
        // to avoid '#' Markdown characteres being interpreted as YAML comments,
        // we replace any leading '#' character by its HTML entity
        $yamlPosts = file_get_contents(__DIR__.'/../config/blog_posts.yml');
        $yamlPosts = preg_replace('/[ ]{8}#/', '        &#35;', $yamlPosts);

        $posts = Yaml::parse($yamlPosts);

        $htmlPosts = array();
        foreach ($posts as $post) {
            // after YAML contents parsing, we replace back the HTML entity by
            // its original '#' character
            $content = preg_replace('/^&#35;/m', '#', $post['content']);

            $slug = $app['slugger']->slugify($post['title']);

            $htmlPosts[$slug] = array(
                'title'        => $post['title'],
                'slug'         => $slug,
                'published_at' => new \DateTime($post['date']),
                'content'      => $app['markdown']->toHtml($content),
            );
        }

        file_put_contents($cachedPostsFile, serialize($htmlPosts));
    }

    $posts = unserialize(file_get_contents($cachedPostsFile));

    return $posts;
}