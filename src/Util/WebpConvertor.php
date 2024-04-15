<?php declare(strict_types=1);

namespace Yireo\Webp\Util;
use Shopware\Core\Content\Media\MediaEndity;
use League\Flysystem\FileNotFoundException;
use Symfony\Component\Asset\UrlPackage;
use Symfony\Component\HttpKernel\KernelInterface;
use WebPConvert\Convert\Exceptions\ConversionFailedException;
use WebPConvert\WebPConvert;

class WebpConvertor
{
    /**
     * @var KernelInterface
     */
    private $kernel;

    /**
     * @var UrlPackage
     */
    private $urlPackage;

    /**
     * WebpConvertor constructor.
     * @param KernelInterface $kernel
     * @param UrlPackage $urlPackage
     */
    public function __construct(
        KernelInterface $kernel,
        UrlPackage $urlPackage
    ) {
        $this->kernel = $kernel;
        $this->urlPackage = $urlPackage;
    }

    /**
     * @param Shopware\Core\Content\Media\MediaEntity $media
     * @return string
     */
    public function convertImageUrl(\Shopware\Core\Content\Media\MediaEntity $media): string
    {
        $imagePath = $media->getPath();
        $webpPath = preg_replace('/\.(png|jpg)$/', '.webp', $imagePath);
        if ($this->shouldConvert($imagePath, $webpPath) === false) {
            return $imageUrl;
        }

        $options = $this->getOptions();

        try {
            WebPConvert::convert($imagePath, $webpPath, $options);
        } catch (ConversionFailedException $e) {
            return $imageUrl;
        }

        $webpUrl = preg_replace('/\.(png|jpg)$/', '.webp', $imageUrl);
        return $webpUrl;
    }

    /**
     * @return array
     */
    private function getOptions(): array
    {
        $options = [];
        $options['metadata'] = 'none';

        return $options;
    }

    /**
     * @param $imagePath
     * @param $webpPath
     * @return bool
     */
    private function shouldConvert($imagePath, $webpPath): bool
    {
        if ($imagePath === $webpPath) {
            return false;
        }

        if (!file_exists($webpPath)) {
            return true;
        }

        if (filemtime($imagePath) < filemtime($webpPath)) {
            return true;
        }

        return false;
    }

    /**
     * @param string $imageUrl
     * @return string
     * @throws FileNotFoundException
     */
    private function getFileFromImageUrl(string $imageUrl): string
    {
        $imagePath = $this->getPublicDirectory() . str_replace($this->urlPackage->getBaseUrl($imageUrl), '', $imageUrl);
        if (!file_exists($imagePath)) {
            throw new FileNotFoundException($imagePath);
        }

        return $imagePath;
    }

    /**
     * @return string
     */
    private function getPublicDirectory(): string
    {
        return $this->kernel->getProjectDir() . '/public';
    }
}
