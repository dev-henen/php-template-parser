<?php 

namespace template;

use Exception;
use Psr\Log\LoggerInterface;

class TemplateException extends Exception {}

class Loader
{
    private $template;
    private $templateFolder = 'tmpl';
    private $maxTemplateIncludes = 5;
    private $cachingConfig = ['allow' => false, 'max_store_age' => 24];
    private $params = [];
    private $loops = [];
    private $conditionals = [];
    private $logger;
    public $show_errors = false;
    public $show_warnings = false;
    private $base_template;
    private $blocks = [];
    private $tokens = [];

    public function __construct($template_name, LoggerInterface $logger = null)
    {
        $this->logger = $logger;
        $this->loadTemplate($template_name);
    }

    private function loadTemplate($template_name)
    {
        $template_path = $this->templateFolder . '/' . $template_name . '.tpl';
        if ($this->cachingConfig['allow'] && $this->isTemplateCached($template_path)) {
            $this->template = $this->getCachedTemplate($template_path);
        } elseif (file_exists($template_path)) {
            $this->template = file_get_contents($template_path);
            if ($this->cachingConfig['allow']) {
                $this->cacheTemplate($template_path, $this->template);
            }
        } else {
            $this->handleError("Template file '{$template_path}' not found.");
        }

        if ($this->template) {
            $this->tokenizeTemplate();
            $this->parseInheritance();
        } else {
            if ($this->show_errors) {
                echo "Error: Template file not found.";
            }
        }
    }

    private function isTemplateCached($template_path)
    {
        $cache_file = $this->getCacheFilePath($template_path);
        if (file_exists($cache_file)) {
            $file_age = (time() - filemtime($cache_file)) / 3600;
            return $file_age <= $this->cachingConfig['max_store_age'];
        }
        return false;
    }

    private function getCachedTemplate($template_path)
    {
        return file_get_contents($this->getCacheFilePath($template_path));
    }

    private function cacheTemplate($template_path, $template_content)
    {
        file_put_contents($this->getCacheFilePath($template_path), $template_content);
    }

    private function getCacheFilePath($template_path)
    {
        return sys_get_temp_dir() . '/' . md5($template_path) . '.cache';
    }

    public function setTemplateFolder($folder)
    {
        $this->templateFolder = $folder;
    }

    public function setMaxTemplateIncludes($max_includes)
    {
        $this->maxTemplateIncludes = $max_includes;
    }

    public function enableCaching($allow, $max_store_age = 24)
    {
        $this->cachingConfig['allow'] = $allow;
        $this->cachingConfig['max_store_age'] = $max_store_age;
    }

    public function set($param_name, $param_value)
    {
        if (!is_string($param_name)) {
            $this->handleError("Parameter name must be a string.");
        }
        $this->params[$param_name] = htmlspecialchars($param_value, ENT_QUOTES, 'UTF-8');
    }

    public function forEach($identifier, $array_or_object)
    {
        if (!is_string($identifier)) {
            $this->handleError("Loop identifier must be a string.");
        }
        if (is_object($array_or_object)) {
            $array_or_object = (array) $array_or_object; // Convert object to array
        }
        if (!is_array($array_or_object)) {
            $this->handleError("Data for loop must be an array or object.");
        }
        $this->loops[$identifier] = $array_or_object;
    }

    public function for($array_identifier, $index_array)
    {
        if (!is_string($array_identifier)) {
            $this->handleError("Array identifier must be a string.");
        }
        if (!is_array($index_array)) {
            $this->handleError("Data for loop must be an array.");
        }
        $this->loops[$array_identifier] = $index_array;
    }

    public function extend($base_template_name)
    {
        $this->base_template = $base_template_name;
    }

    public function block($block_name, $block_content)
    {
        if (!is_string($block_name)) {
            $this->handleError("Block name must be a string.");
        }
        $this->blocks[$block_name] = $block_content;
    }

    private function tokenizeTemplate()
    {
        $pattern = '/(@[a-zA-Z]+\[.*?\]|\{\{.*?\}\}|@end\[.*?\])/';
        $this->tokens = preg_split($pattern, $this->template, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
    }

    private function parseTokens()
    {
        foreach ($this->tokens as $token) {
            if (preg_match('/@include\[([^\]]+)\]/', $token, $matches)) {
                $include = $matches[1];
                $include_path = $this->templateFolder . '/' . $include . '.tpl';
                if (file_exists($include_path)) {
                    $include_content = file_get_contents($include_path);
                    $this->template = str_replace($token, $include_content, $this->template);
                } else {
                    $this->handleWarning("Include file '{$include_path}' not found.");
                    $this->template = str_replace($token, "<!-- Warning: Include file not found -->", $this->template);
                }
            } elseif (preg_match('/\{\{(.*?)\}\}/', $token, $matches)) {
                $param = $matches[1];
                if (isset($this->params[$param])) {
                    $this->template = str_replace($token, $this->params[$param], $this->template);
                }
            }
        }
    }

    private function parseIncludes()
    {
        preg_match_all('/@include\[([^\]]+)\]/', $this->template, $matches);
        if (count($matches[1]) > $this->maxTemplateIncludes) {
            $this->handleError("Exceeded max template includes ({$this->maxTemplateIncludes}).");
        }
        foreach ($matches[1] as $include) {
            $include_path = $this->templateFolder . '/' . $include . '.tpl';
            if (file_exists($include_path)) {
                $include_content = file_get_contents($include_path);
                $this->template = str_replace("@include[$include]", $include_content, $this->template);
            } else {
                $this->handleWarning("Include file '{$include_path}' not found.");
                $this->template = str_replace("@include[$include]", "<!-- Warning: Include file not found -->", $this->template);
            }
        }
    }

    private function parseParams()
    {
        foreach ($this->params as $key => $value) {
            $this->template = str_replace("@{{$key}}", $value, $this->template);
        }
    }

    private function parseLoops()
    {
        foreach ($this->loops as $key => $data) {
            // Check if $data is an associative array and wrap it in an array
            if (array_values($data) !== $data) {
                $data = [$data];
            }

            preg_match("/@forEach\[$key\](.*?)@end\[$key\]/s", $this->template, $match);
            if ($match) {
                $loop_template = $match[1];
                $rendered = '';
                foreach ($data as $item_value) {
                    $item_render = $loop_template;
                    if (is_array($item_value) || is_object($item_value)) {
                        foreach ($item_value as $sub_key => $sub_value) {
                            $item_render = str_replace("{{{$sub_key}}}", htmlspecialchars($sub_value, ENT_QUOTES, 'UTF-8'), $item_render);
                        }
                    } else {
                        $item_render = str_replace("{{{$key}}}", htmlspecialchars($item_value, ENT_QUOTES, 'UTF-8'), $item_render);
                    }
                    $rendered .= $item_render;
                }
                $this->template = str_replace($match[0], $rendered, $this->template);
            }

            preg_match("/@for\[$key\](.*?)@end\[$key\]/s", $this->template, $match);
            if ($match) {
                $loop_template = $match[1];
                $rendered = '';
                foreach ($data as $item_value) {
                    $item_render = str_replace("{{value}}", htmlspecialchars($item_value, ENT_QUOTES, 'UTF-8'), $loop_template);
                    $rendered .= $item_render;
                }
                $this->template = str_replace($match[0], $rendered, $this->template);
            }
        }
    }

    private function parseInheritance()
    {
        // Check for template inheritance
        if (preg_match('/@extend\[([^\]]+)\]/', $this->template, $match)) {
            $base_template_name = $match[1];
            $base_template_path = $this->templateFolder . '/' . $base_template_name . '.tpl';
            if (file_exists($base_template_path)) {
                $base_template = file_get_contents($base_template_path);
                $this->template = str_replace($match[0], '', $this->template); // Remove @extend directive
                preg_match_all('/@block\[([^\]]+)\](.*?)@end\[\1\]/s', $this->template, $matches, PREG_SET_ORDER);
                foreach ($matches as $match) {
                    $block_name = $match[1];
                    $block_content = $match[2];
                    $this->blocks[$block_name] = $block_content;
                    $this->template = str_replace($match[0], '', $this->template); // Remove blocks from child template
                }
                $this->template = $base_template . $this->template; // Prepend base template content
            } else {
                if ($this->show_warnings) {
                    echo "<!-- Warning: Base template file not found -->";
                }
            }
        }
    }

    private function replaceBlocks()
    {
        // Replace blocks in the base template
        foreach ($this->blocks as $block_name => $block_content) {
            $this->template = preg_replace("/@block\[$block_name\](.*?)@end\[$block_name\]/s", $block_content, $this->template);
        }
    }

    public function if($identifier, $expr)
    {
        if (!is_string($identifier)) {
            $this->handleError("Conditional identifier must be a string.");
        }
        if (!is_bool($expr)) {
            $this->handleError("Expression must be a boolean.");
        }
        $this->conditionals[$identifier] = $expr;
    }
    
    private function parseConditionals()
    {
        foreach ($this->conditionals as $key => $expr) {
            if ($expr) {
                $this->template = preg_replace("/@if\[$key\]\(expr\)(.*?)@else\[$key\](.*?)@end\[$key\]/s", '$1', $this->template);
                $this->template = preg_replace("/@if\[$key\]\(expr\)(.*?)@end\[$key\]/s", '$1', $this->template);
            } else {
                $this->template = preg_replace("/@if\[$key\]\(expr\)(.*?)@else\[$key\](.*?)@end\[$key\]/s", '$2', $this->template);
                $this->template = preg_replace("/@if\[$key\]\(expr\)(.*?)@end\[$key\]/s", '', $this->template);
            }
        }
    }

    public function render($keep_comments = true)
    {
        $this->parseTokens();
        $this->parseIncludes();
        $this->parseParams();
        $this->parseLoops();
        $this->replaceBlocks();
        $this->parseConditionals();
        if (!$keep_comments) {
            $this->template = preg_replace('/<!--(.*?)-->/', '', $this->template);
        }
        echo $this->template;
    }

    private function handleError($message)
    {
        if ($this->show_errors) {
            echo "Error: $message";
        }
        if ($this->logger) {
            $this->logger->error($message);
        } else {
            error_log("Error: $message");
        }
        throw new TemplateException($message);
    }

    private function handleWarning($message)
    {
        if ($this->show_warnings) {
            echo "Warning: $message";
        }
        if ($this->logger) {
            $this->logger->warning($message);
        } else {
            error_log("Warning: $message");
        }
    }
}
