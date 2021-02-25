<?php
/**
 * Created by PhpStorm.
 * User: vincentvalot
 * Date: 25/04/14
 * Time: 10:48
 */

namespace Api\ApiBundle\EventListener;

use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernel;

class RequestListener
{

    protected $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        if (HttpKernel::MASTER_REQUEST === $event->getRequestType()) {
            if ($event->getRequest()->getMethod() === 'PUT'
                && preg_match('#multipart/form-data#', $event->getRequest()->headers->get('Content-Type'))
                && !count($event->getRequest()->request)
            ) {
//                echo "-------------REQUEST-------------------";
//                echo "<pre>";
//                print_r($event->getRequest());
//                echo "</pre>";
//                echo "----------------------------------";
                $this->parseRequest($event->getRequest());

                $this->container->set('request', $event->getRequest());
//                die();
            }
        }
    }

    /**
     * Parse une requête
     *
     * @param Request $request
     * @return array
     */
    protected function parseRequest(Request $request)
    {
        // read incoming data
        $input = $request->getContent();
//        echo "-------------INPUT-------------------";
//        echo "<pre>";
//        print_r($input);
//        echo "</pre>";
//        echo "----------------------------------";

        $data = array();
        $files = array();

        // grab multipart boundary from content type header
        preg_match('/boundary=(.*)$/', $request->headers->get('Content-Type'), $matches);
        $boundary = $matches[1];
//        echo "-------------BOUNDARY-------------------";
//        echo "<pre>";
//        print_r($boundary);
//        echo "</pre>";
//        echo "----------------------------------";

        // split content by boundary and get rid of last -- element
        $a_blocks = preg_split("/-+$boundary/", $input);
        array_pop($a_blocks);

        // loop data blocks
        foreach ($a_blocks as $block) {
            if (empty($block)) {
                continue;
            }

            $headers = $this->getHeaders($block);

//            echo "-------------BLOC-------------------";
//            echo "<pre>";
//            print_r($block);
//            echo "</pre>";
//            echo "----------------------------------";

//            echo "-------------HEADERS-------------------";
//            echo "<pre>";
//            print_r($headers);
//            echo "</pre>";
//            echo "----------------------------------";

            // you'll have to var_dump $block to understand this and maybe replace \n or \r with a visibile char

            // parse uploaded files
            if (strpos($block, 'application/octet-stream') !== false) {
                // match "name", then everything after "stream" (optional) except for prepending newlines
                preg_match("/; *name=\"([^\"]*)\".*stream[\n|\r]+([^\n\r].*)?/s", $block, $matches);
            } // parse all other fields
            else {
                // match "name" and optional value in between newline sequences
                preg_match('/; *name=\"([^\"]*)\"(; *filename=\"[^\"]*\")?[\n|\r]+([^\n\r].*)?\r/s', $block, $matches);
            }
            $matchFile = array();
            if (preg_match('/filename="([^"]+)"/', $block, $matchFile)) {

                $filename_parts = pathinfo($matchFile[1]);
                $tmp_name = tempnam(ini_get('upload_tmp_dir'), $filename_parts['filename']);

                $matches[3] = preg_replace('#Content-Type: [a-z]+/[a-z\-]+[\n|\r]+#', '', $matches[3]);
                file_put_contents($tmp_name, $matches[3]);

                $this->parseBlock($files, array(
                    "name" => $matches[1],
                    "value" => array(
                        'error' => 0,
                        'name' => $matchFile[1],
                        'tmp_name' => $tmp_name,
                        'size' => strlen($matches[3]),
                        'type' => $headers['Content-Type']
                    )
                ));
            } else if (isset($matches[3])) {
                $this->parseBlock($data, array("name" => $matches[1], "value" => $matches[3]));
            }
        }

        $request->request->replace($data);
        $request->files->replace($files);
    }

    /**
     * Retourne les entêtes d'un block
     *
     * @param $block
     * @return array
     */
    protected function getHeaders($block)
    {
        $part = ltrim($block, "\r\n");
        list($raw_headers,) = explode("\r\n\r\n", $part, 2);
        $raw_headers = explode("\r\n", $raw_headers);

        $headers = array();
        foreach ($raw_headers as $header) {
            list($name, $value) = explode(':', $header);
            $headers[$name] = ltrim($value, ' ');
        }

        return $headers;
    }

    /**
     * Parse un block, avec récursivité
     *
     * @param $data
     * @param $block
     */
    protected function parseBlock(&$data, $block)
    {
        $matches = array();

        if (preg_match("#^([a-zA-Z0-9]+)((\[.*\])+)#", $block["name"], $matches)) {
            if (!isset($data[$matches[1]])) $data[$matches[1]] = array();

            preg_match_all("/\[([a-zA-Z0-9]+)\]/", $matches[2], $children);
            list($crochets, $net) = $children;
            unset($crochets[0]);

            $this->parseBlock($data[$matches[1]], array("name" => $net[0] . join('', $crochets), "value" => $block["value"]));
        } else {
            if (isset($data[$block["name"]])) $data[$block["name"]][] = $block["value"];
            else $data[$block["name"]] = $block["value"];
        }

    }
}