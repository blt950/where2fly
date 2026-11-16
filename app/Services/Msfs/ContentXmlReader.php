<?php

namespace App\Services\Msfs;

use XMLReader;

class ContentXmlReader
{
    /**
     * The `name` and `active` of every <Package>, in file order. Validity is decided here, not by MIME type.
     *
     * @return list<array{name: ?string, active: ?string}>
     *
     * @throws ContentXmlException
     */
    public function read(string $path, int $maxBytes): array
    {
        if (filesize($path) > $maxBytes) {
            throw new ContentXmlException('The file is too large to be a Content.xml.', 'too_large');
        }

        $previous = libxml_use_internal_errors(true);
        libxml_clear_errors();
        $reader = new XMLReader;

        try {
            // No LIBXML_NOENT: entities are never substituted, and a DOCTYPE is refused below
            if (! $reader->open($path, null, LIBXML_NONET)) {
                throw $this->invalid();
            }

            $rootSeen = false;
            $packages = [];

            while ($reader->read()) {
                if ($reader->nodeType === XMLReader::DOC_TYPE) {
                    throw new ContentXmlException('The file contains a DOCTYPE, which a Content.xml never has.', 'invalid_xml');
                }

                if ($reader->nodeType !== XMLReader::ELEMENT) {
                    continue;
                }

                if ($reader->depth === 0) {
                    if ($reader->name !== 'Packages') {
                        throw new ContentXmlException(
                            'This is not an MSFS 2024 Content.xml: it starts with <' . mb_substr($reader->name, 0, 40) . '> instead of <Packages>. Files from MSFS 2020 are not supported.',
                            'unexpected_root'
                        );
                    }
                    $rootSeen = true;
                } elseif ($reader->depth === 1 && $reader->name === 'Package') {
                    $packages[] = ['name' => $reader->getAttribute('name'), 'active' => $reader->getAttribute('active')];
                }
            }

            foreach (libxml_get_errors() as $error) {
                if ($error->level >= LIBXML_ERR_ERROR) {
                    throw $this->invalid();
                }
            }

            if (! $rootSeen) {
                throw $this->invalid();
            }

            if ($packages === []) {
                throw new ContentXmlException('The file lists no packages.', 'empty');
            }

            return $packages;
        } finally {
            $reader->close();
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }

    private function invalid(): ContentXmlException
    {
        return new ContentXmlException('The file could not be read as XML. Upload the Content.xml that MSFS 2024 writes.', 'invalid_xml');
    }
}
