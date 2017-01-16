<?php
namespace TYPO3\CMS\Composer\Plugin\Core;

/*
 * This file is part of the TYPO3 project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Semver\Constraint\EmptyConstraint;
use TYPO3\CMS\Composer\Plugin\Config;
use TYPO3\CMS\Composer\Plugin\Util\Filesystem;

/**
 * TYPO3 Core installer
 *
 * @author Helmut Hummel <info@helhum.io>
 */
class WebDirectory
{
    const TYPO3_DIR = 'typo3';
    const TYPO3_INDEX_PHP = 'index.php';

    /**
     * @var IOInterface
     */
    private $io;

    /**
     * @var array
     */
    private $symlinks = [];

    /**
     * @var Composer
     */
    private $composer;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var Config
     */
    private $pluginConfig;

    /**
     * @param IOInterface $io
     * @param Composer $composer
     * @param Filesystem $filesystem
     * @param Config $pluginConfig
     */
    public function __construct(IOInterface $io, Composer $composer, Filesystem $filesystem, Config $pluginConfig)
    {
        $this->io = $io;
        $this->composer = $composer;
        $this->filesystem = $filesystem;
        $this->pluginConfig = $pluginConfig;
    }

    public function ensureSymlinks()
    {
        $this->initializeSymlinks();
        if ($this->filesystem->someFilesExist($this->symlinks)) {
            $this->filesystem->removeSymlinks($this->symlinks);
        }
        $this->filesystem->establishSymlinks($this->symlinks, false);
    }

    /**
     * Initialize symlinks with configuration
     */
    private function initializeSymlinks()
    {
        if ($this->composer->getPackage()->getName() === 'typo3/cms') {
            // Nothing to do typo3/cms is root package
            return;
        }
        if ($this->pluginConfig->get('prepare-web-dir') === false) {
            return;
        }
        $this->io->writeError('<info>Establishing links to TYPO3 entry scripts in web directory</info>', true, IOInterface::VERBOSE);

        $webDir = $this->filesystem->normalizePath($this->pluginConfig->get('web-dir'));
        $this->filesystem->ensureDirectoryExists($webDir);
        $sourcesDir = $this->determineInstallPath();
        $backendDir = $webDir . DIRECTORY_SEPARATOR . self::TYPO3_DIR;
        $this->symlinks = [
            $sourcesDir . DIRECTORY_SEPARATOR . self::TYPO3_INDEX_PHP
                => $webDir . DIRECTORY_SEPARATOR . self::TYPO3_INDEX_PHP,
            $sourcesDir . DIRECTORY_SEPARATOR . self::TYPO3_DIR
                => $backendDir
        ];
    }

    private function determineInstallPath()
    {
        $localRepository = $this->composer->getRepositoryManager()->getLocalRepository();
        $package = $localRepository->findPackage('typo3/cms', new EmptyConstraint());
        return $this->composer->getInstallationManager()->getInstallPath($package);
    }
}