<?php
    /*
     MIT License
     
     Copyright (c) 2017 Michael Koval
     
     Permission is hereby granted, free of charge, to any person obtaining a copy
     of this software and associated documentation files (the "Software"), to deal
     in the Software without restriction, including without limitation the rights
     to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
     copies of the Software, and to permit persons to whom the Software is
     furnished to do so, subject to the following conditions:
     
     The above copyright notice and this permission notice shall be included in all
     copies or substantial portions of the Software.
     
     THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
     IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
     FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
     AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
     LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
     OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
     SOFTWARE.
     */
class SitemapManager
{

    var $printDebug                    = false;
    private static $sitemapIndexFileName  = 'sitemap_index.xml';
    private static $sitemapLinkDomain     = 'http://sps-app.com/';
    private static $sitemapLinkFileName   = 'sitemap';
    private static $sitemapLinkFileExt    = '.xml';
    private static $sitemapLinkGZEnding   = '.gz';
    private static $maxSitemapURLs        = 50000; //50K
    private static $maxSitemapFileSize    = 10000000; //10Mb
    private static $extraSitemapSizeBytes = 100;
    #==========================================================================
    private static $lastModTag            = 'lastmod';
    private static $urlTag                = 'url';
    private static $locTag                = 'loc';
    private static $sitemapindexTag       = 'sitemapindex';
    private static $sitemapTag            = 'sitemap';
    private static $urlSetTag             = 'urlset';
    private static $xmlnsAttribute        = 'xmlns';
    private static $schemaLink            = 'http://www.sitemaps.org/schemas/sitemap/0.9';

    function __construct()
    {
        
    }

    function addNewUrlToSitemap($url)
    {
        if ($this->isNullOrEmptyString($url))
        {
            $this->printDebugMessageNewLine('Failed to insert URL. Undefined.');
            exit;
        }

        if (filter_var($url, FILTER_VALIDATE_URL) === FALSE)
        {
            $this->printDebugMessageNewLine('Failed to insert URL. "' . $url . '" is not a URL.');
            exit;
        }

        if (file_exists(self::$sitemapIndexFileName) === FALSE)
        {
            $this->printDebugMessageNewLine('No sitemap index exist with name "' . self::$sitemapIndexFileName . '"');
            $this->createNewSitemapIndex();
        }

        $lastSitemapId = $this->findLastSitemapId();
        $this->insertNewUrlToSitemapFile($lastSitemapId, $url);
    }

    /*
     * PRIVATE FUNCTIONS
     */

    private function isNullOrEmptyString($question)
    {
        return (!isset($question) || trim($question) === '');
    }

    private function findLastSitemapId()
    {
        if (file_exists(self::$sitemapIndexFileName))
        {
            $xml   = simplexml_load_file(self::$sitemapIndexFileName);
            $maxId = 0;
            foreach ($xml->sitemap as $sitemap)
            {
                $id = $this->getSitemapIdFromFileName($sitemap->loc);
                if ($id > $maxId)
                {
                    $maxId = $id;
                }
            }

            $this->printDebugMessageNewLine('Last sitemap file name: "' . $this->getSiteMapFileNameForId($maxId) . '"');
            if ($maxId == 0)
            {
                $this->printDebugMessageNewLine('No sitemap file exist' . '<br>');
                $maxId              = 1;
                $this->createNewSitemapFile($maxId);
                $newSitemapFileName = $this->getSiteMapFileNameForId($maxId);
                $this->insertNewSitemapFileToIndex($newSitemapFileName);
            }


            return $maxId;
        }
        else
        {
            $this->printDebugMessageNewLine('Failed to open ' . self::$sitemapIndexFileName);
            exit;
        }
    }

    private function getSiteMapFileNameForId($id)
    {
        return self::$sitemapLinkFileName . $id . self::$sitemapLinkFileExt . self::$sitemapLinkGZEnding;
    }

    private function getSitemapIdFromFileName($fileName)
    {
        $fileName1 = str_replace(self::$sitemapLinkDomain, "", $fileName);
        $fileName2 = str_replace(self::$sitemapLinkFileName, "", $fileName1);
        $fileName3 = str_replace(self::$sitemapLinkFileExt, "", $fileName2);
        $fileName4 = str_replace(self::$sitemapLinkGZEnding, "", $fileName3);
        return intval($fileName4);
    }

    private function createNewSitemapIndex()
    {
        $this->printDebugMessageNewLine('Creating new sitemap index with name: "' . self::$sitemapIndexFileName . '"');

        $domDocument = new DOMDocument('1.0', 'UTF-8');


        $domElement          = $domDocument->createElement(self::$sitemapindexTag);
        $domAttribute        = $domDocument->createAttribute(self::$xmlnsAttribute);
        $domAttribute->value = self::$schemaLink;
        $domElement->appendChild($domAttribute);

        $domDocument->appendChild($domElement);

        $domDocument->save(self::$sitemapIndexFileName);
    }

    private function insertNewSitemapFileToIndex($sitemapFileName)
    {
        $fullFilePath = self::$sitemapLinkDomain . $sitemapFileName;
        $this->printDebugMessageNewLine('Inserting new sitemap file to index with name: "' . $fullFilePath . '"');
        $dom          = DOMDocument::load(self::$sitemapIndexFileName);


        $root = $dom->getElementsByTagName(self::$sitemapindexTag)->item(0);

        $sitemapEl = $dom->createElement(self::$sitemapTag);
        $loc       = $dom->createElement(self::$locTag, $fullFilePath);
        $sitemapEl->appendChild($loc);
        $root->appendChild($sitemapEl);

        $dom->save(self::$sitemapIndexFileName);
    }

    private function createNewSitemapFile($index)
    {
        $newSitemapFile = str_replace(self::$sitemapLinkGZEnding, '', $this->getSiteMapFileNameForId($index));

        $this->printDebugMessageNewLine('Creating new sitemap file with name: "' . $newSitemapFile . '"');
        /* create a dom document with encoding utf8 */
        $domDocument = new DOMDocument('1.0', 'UTF-8');


        /* create the root element of the xml tree */
        $domElement          = $domDocument->createElement(self::$urlSetTag);
        $domAttribute        = $domDocument->createAttribute(self::$xmlnsAttribute);
        $domAttribute->value = self::$schemaLink;
        $domElement->appendChild($domAttribute);

        $domDocument->appendChild($domElement);




        $domDocument->save($newSitemapFile);

        exec("gzip " . $newSitemapFile);
    }

    private function insertNewUrlToSitemapFile($fileId, $url)
    {
        $compressedSitemapFile = $this->getSiteMapFileNameForId($fileId);
        $sitemapFile           = str_replace(self::$sitemapLinkGZEnding, '', $this->getSiteMapFileNameForId($fileId));

        $this->printDebugMessageNewLine('Extracting sitemap file with name: "' . $compressedSitemapFile . '"');
        exec("gunzip " . $compressedSitemapFile);


        $dom    = DOMDocument::load($sitemapFile);
        $urlset = $dom->getElementsByTagName(self::$urlSetTag);


        $this->printDebugMessageNewLine('Currently there are ' . $urlset->item(0)->getElementsByTagName(self::$urlTag)->length . ' URLs in this file.');
        $this->printDebugMessageNewLine('Currently file size is: ' . filesize($sitemapFile) . ' Bytes.');
        if ($urlset->item(0)->getElementsByTagName(self::$urlTag)->length >= self::$maxSitemapURLs)
        {
            $this->printDebugMessageNewLine('Cannot insert new URL to file with name: "' . $compressedSitemapFile . '" because it has maximum entries(' . self::$maxSitemapURLs . ')');
            exec("gzip " . $sitemapFile);
            $this->insertUrlInToNewSitemapFile($fileId, $url);
            return;
        }

        $expectedFileSize = filesize($sitemapFile) + strlen($url) + strlen('<url><loc></url></loc>') + 100;
        if ($expectedFileSize > self::$maxSitemapFileSize)
        {
            $this->printDebugMessage('Cannot insert new URL to file with name: "' . $compressedSitemapFile . '" because it exceeds its size(' . filesize($sitemapFile) . ')');
            $this->printDebugMessage(' + ' . strlen($url));
            $this->printDebugMessage(' + ' . strlen('<url><loc></url></loc>'));
            $this->printDebugMessage(' + ' . self::$extraSitemapSizeBytes);
            $this->printDebugMessage(' = ' . $expectedFileSize);
            $this->printDebugMessageNewLine('<br>');
            exec("gzip " . $sitemapFile);
            $this->insertUrlInToNewSitemapFile($fileId, $url);
            return;
        }


        $this->printDebugMessageNewLine('Inserting URL: "' . $url . '" in to file with name: "' . $compressedSitemapFile);
        $root  = $urlset->item(0);
        $urlEl = $dom->createElement(self::$urlTag);
        $loc   = $dom->createElement(self::$locTag, $url);
        $urlEl->appendChild($loc);
        $root->appendChild($urlEl);






        $dom->save($sitemapFile);
        exec("gzip " . $sitemapFile);

        $this->updateSitemapIndexWithModifyDate($compressedSitemapFile);
    }

    private function updateSitemapIndexWithModifyDate($compressedSitemapFile)
    {
        $this->printDebugMessageNewLine('Updating "lastmod" for file: "' . $compressedSitemapFile . '" with current date:' . (new \DateTime())->format(DATE_W3C));

        $dom = DOMDocument::load(self::$sitemapIndexFileName);

        $root     = $dom->getElementsByTagName(self::$sitemapindexTag)->item(0);
        $siteMaps = $root->getElementsByTagName(self::$sitemapTag);
        for ($i = 0; $i <= $siteMaps->length; $i++)
        {
            $sitemap = $siteMaps->item($i);
            if (strpos($sitemap->getElementsByTagName(self::$locTag)->item(0)->nodeValue, $compressedSitemapFile) !== false)
            {
                $this->printDebugMessageNewLine('Found Node. Updating...');
                $lastMod = $dom->createElement(self::$lastModTag, (new \DateTime())->format(DATE_W3C));
                if ($sitemap->getElementsByTagName(self::$lastModTag)->length == 0)
                {
                    $this->printDebugMessageNewLine('Adding "lastmod" node...');
                    $sitemap->appendChild($lastMod);
                }
                else
                {
                    $this->printDebugMessageNewLine('"lastmod" already exists. Replacing...');
                    $lastModOld = $sitemap->getElementsByTagName(self::$lastModTag)->item(0);
                    $lastModOld->parentNode->replaceChild($lastMod, $lastModOld);
                }
                break;
            }
        }

        $dom->save(self::$sitemapIndexFileName);
    }

    private function insertUrlInToNewSitemapFile($currentFileId, $url)
    {
        $newFileId          = $currentFileId + 1;
        $this->createNewSitemapFile($newFileId);
        $newSitemapFileName = $this->getSiteMapFileNameForId($newFileId);
        $this->insertNewSitemapFileToIndex($newSitemapFileName);
        $this->insertNewUrlToSitemapFile($newFileId, $url);
    }

    private function printDebugMessage($message)
    {
        if ($this->printDebug == false)
        {
            return;
        }

        echo $message;
    }

    private function printDebugMessageNewLine($message)
    {
        if ($this->printDebug == false)
        {
            return;
        }
        $this->printDebugMessage($message);
        echo '<br>';
    }

}

?>

