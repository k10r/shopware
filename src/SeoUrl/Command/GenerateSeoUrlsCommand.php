<?php
/**
 * Shopware 5
 * Copyright (c) shopware AG
 *
 * According to our dual licensing model, this program can be used either
 * under the terms of the GNU Affero General Public License, version 3,
 * or under a proprietary license.
 *
 * The texts of the GNU Affero General Public License with an additional
 * permission and of our proprietary license can be found at and
 * in the LICENSE file you have received along with this program.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * "Shopware" is a registered trademark of shopware AG.
 * The licensing of the program under the AGPLv3 does not imply a
 * trademark license. Therefore any rights, title and interest in
 * our trademarks remain entirely with us.
 */

namespace Shopware\SeoUrl\Command;

use Shopware\Context\Struct\TranslationContext;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateSeoUrlsCommand extends ContainerAwareCommand
{
    protected function configure(): void
    {
        $this
            ->setName('seo:url:generate')
            ->addOption('force', 'f')
            ->setDescription('Generates all seo urls')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $shops = $this->getContainer()->get('dbal_connection')->fetchAll(
            'SELECT uuid, fallback_locale_uuid, `is_default` FROM shop'
        );

        $generatorRegistry = $this->getContainer()->get('shopware.seo_url.generator.seo_url_generator_registry');

        foreach ($shops as $shop) {
            $context = new TranslationContext(
                (string) $shop['uuid'],
                (bool) $shop['is_default'],
                $shop['fallback_locale_uuid'] ? (int) $shop['fallback_locale_uuid'] : null
            );

            $generatorRegistry->generate(
                $context->getShopUuid(),
                $context,
                (bool) $input->getOption('force')
            );
        }
    }
}