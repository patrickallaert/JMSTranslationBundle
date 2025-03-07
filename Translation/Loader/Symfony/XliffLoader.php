<?php

declare(strict_types=1);

/*
 * Copyright 2011 Johannes M. Schmitt <schmittjoh@gmail.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace JMS\TranslationBundle\Translation\Loader\Symfony;

use JMS\TranslationBundle\Exception\RuntimeException;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Translation\Loader\LoaderInterface;
use Symfony\Component\Translation\MessageCatalogue;

/**
 * XLIFF loader.
 *
 * This loader replaces Symfony's default loader which uses the source element
 * as the id whereas this loader uses the resname to conform to the XLIFF
 * specification.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
// phpcs:ignore
class XliffLoaderInternal
{
    protected function loadInternal($resource, $locale, $domain = 'messages')
    {
        $previous = libxml_use_internal_errors(true);
        if (false === $xml = simplexml_load_file((string) $resource)) {
            libxml_use_internal_errors($previous);
            $error = libxml_get_last_error();

            throw new RuntimeException(sprintf('An error occurred while reading "%s": %s', $resource, $error->message));
        }

        libxml_use_internal_errors($previous);

        $xml->registerXPathNamespace('xliff', 'urn:oasis:names:tc:xliff:document:1.2');

        $catalogue = new MessageCatalogue($locale);
        foreach ($xml->xpath('//xliff:trans-unit') as $translation) {
            $resName = (string) $translation->attributes()->resname;
            $id = $resName ?: (string) $translation->source;

            $catalogue->set($id, (string) $translation->target, $domain);
        }

        $catalogue->addResource(new FileResource((string) $resource));

        return $catalogue;
    }
}

$isSf6 = version_compare(Kernel::VERSION, '6.0.0') >= 0;

if ($isSf6) {
    // phpcs:ignore
    class XliffLoader extends XliffLoaderInternal implements LoaderInterface
    {
        public function load(mixed $resource, string $locale, string $domain = 'messages'): MessageCatalogue
        {
            return $this->loadInternal($resource, $locale, $domain);
        }
    }
} else {
    // phpcs:ignore
    class XliffLoader extends XliffLoaderInternal implements LoaderInterface
    {
        public function load($resource, $locale, $domain = 'messages')
        {
            return $this->loadInternal($resource, $locale, $domain);
        }
    }
}
