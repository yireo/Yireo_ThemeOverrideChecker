<?php declare(strict_types=1);

namespace Yireo\ThemeOverrideChecker\Util;

/**
 * Strips comments and other irrelevant parts from files
 */
class FileStripper
{
    /**
     * @param string $filename
     * @param string $text
     * @return string
     */
    public function strip(string $filename, string $text): string
    {
        if (preg_match('/\.(phtml|html|xml)$/', strtolower($filename))) {
            $text = $this->setStripHtmlComments($text);
        }

        if (preg_match('/\.(js|jsx|ts|tsx|vue)$/', strtolower($filename))) {
            $text = $this->setStripJsComments($text);
        }

        if (preg_match('/\.(css|scss|less)$/', strtolower($filename))) {
            $text = $this->setStripCssComments($text);
        }

        return $text;
    }

    /**
     * @param string $text
     * @return string
     */
    private function setStripHtmlComments(string $text): string
    {
        return preg_replace('#<!--(.|\s)*?-->#msi', '', $text);
    }

    /**
     * @param string $text
     * @return string
     */
    private function setStripJsComments(string $text): string
    {
        $text = preg_replace('#^//(.*)\n#', '', $text);
        $text = preg_replace('#/\*(.*)\*/#msi', '', $text);
        return $text;
    }

    /**
     * @param string $text
     * @return string
     */
    private function setStripCssComments(string $text): string
    {
        $text = preg_replace('#^//(.*)\n#', '', $text);
        $text = preg_replace('#/\*(.*)\*/#msi', '', $text);
        return $text;
    }
}
