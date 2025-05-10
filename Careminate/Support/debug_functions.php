<?php declare(strict_types=1);

if (!function_exists('dd')) {
    /**
     * Dump and die with styled output
     * 
     * @param mixed ...$vars Variables to dump
     * @return never
     */
    function dd(...$vars): never
    {
        $isCli = php_sapi_name() === 'cli';
        
        // Start output buffering if in browser mode
        if (!$isCli) {
            // Apply styling for browser output
            echo '<!DOCTYPE html>
            <html>
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Debug Output</title>
                <style>
                    body {
                        font-family: monospace;
                        background-color: #2d2d2d;
                        color: #f8f8f2;
                        padding: 20px;
                        margin: 0;
                    }
                    .dd-container {
                        margin-bottom: 20px;
                        border: 1px solid #444;
                        border-radius: 4px;
                        overflow: hidden;
                    }
                    .dd-header {
                        background-color: #444;
                        color: #f8f8f2;
                        padding: 8px 15px;
                        font-weight: bold;
                        font-size: 14px;
                        display: flex;
                        justify-content: space-between;
                    }
                    .dd-content {
                        padding: 15px;
                        overflow: auto;
                        max-height: 500px;
                    }
                    pre {
                        margin: 0;
                        white-space: pre-wrap;
                        font-size: 13px;
                        line-height: 1.5;
                    }
                    .string { color: #a6e22e; }
                    .number { color: #ae81ff; }
                    .boolean { color: #66d9ef; }
                    .null { color: #f92672; }
                    .array { color: #fd971f; }
                    .object { color: #a1efe4; }
                    .property { color: #e6db74; }
                    .backtrace { 
                        margin-top: 15px;
                        padding-top: 15px;
                        border-top: 1px dashed #444;
                        font-size: 12px;
                    }
                    .backtrace-title {
                        color: #f92672;
                        margin-bottom: 8px;
                    }
                    .backtrace-item {
                        padding: 3px 0;
                        color: #75715e;
                    }
                    .backtrace-highlight {
                        color: #e6db74;
                    }
                </style>
            </head>
            <body>
                <h1 style="color: #f92672; margin-bottom: 20px;">Debug Dump</h1>';
        }

        // Get the backtrace to show where dd() was called from
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $file = $trace[0]['file'] ?? 'unknown';
        $line = $trace[0]['line'] ?? 0;

        // Format and output each variable
        foreach ($vars as $index => $var) {
            $varName = getVariableName($index, $vars, $trace);
            $type = getHumanReadableType($var);
            
            if (!$isCli) {
                echo '<div class="dd-container">';
                echo '<div class="dd-header">';
                echo '<span>' . htmlspecialchars($varName) . ' (' . $type . ')</span>';
                echo '<span>Called from: ' . htmlspecialchars(basename($file)) . ':' . $line . '</span>';
                echo '</div>';
                echo '<div class="dd-content">';
                echo '<pre>';
                
                // Enhanced output with syntax highlighting for browser
                echo formatVar($var);
                
                echo '</pre>';
                
                // Add backtrace for more context
                echo '<div class="backtrace">';
                echo '<div class="backtrace-title">Stack Trace:</div>';
                
                foreach (array_slice(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS), 1, 3) as $i => $trace) {
                    $function = $trace['function'] ?? '';
                    $traceFile = isset($trace['file']) ? basename($trace['file']) : 'unknown';
                    $traceLine = $trace['line'] ?? 0;
                    $class = isset($trace['class']) ? $trace['class'] . $trace['type'] : '';
                    
                    echo '<div class="backtrace-item">';
                    echo '#' . $i . ' ' . $traceFile . '(' . $traceLine . '): ';
                    echo '<span class="backtrace-highlight">' . $class . $function . '()</span>';
                    echo '</div>';
                }
                
                echo '</div>'; // end backtrace
                echo '</div>'; // end dd-content
                echo '</div>'; // end dd-container
            } else {
                // CLI output
                echo "\n\033[1;36m" . $varName . " (" . $type . "):\033[0m\n";
                var_dump(value($var));
                echo "\033[0;90mCalled from: " . $file . ":" . $line . "\033[0m\n";
            }
        }

        if (!$isCli) {
            echo '</body></html>';
        }
        
        exit(1);
    }
}



/**
 * Try to determine the variable name from debug_backtrace
 *
 * @param int $index
 * @param array $vars
 * @param array $trace
 * @return string
 */
function getVariableName(int $index, array $vars, array $trace): string
{
    // Default name if we can't determine actual variable name
    $defaultName = "Variable #" . ($index + 1);
    
    if (!isset($trace[0]['file'])) {
        return $defaultName;
    }
    
    $file = $trace[0]['file'];
    $line = $trace[0]['line'] ?? 0;
    
    if (!file_exists($file)) {
        return $defaultName;
    }
    
    // Get the line where dd() was called
    $fileContent = file($file);
    if (!isset($fileContent[$line - 1])) {
        return $defaultName;
    }
    
    $callingLine = $fileContent[$line - 1];
    
    // Match dd($var) or dd($var, $var2, ...)
    preg_match('/dd\s*\(\s*(.+?)\s*\)/i', $callingLine, $matches);
    
    if (empty($matches[1])) {
        return $defaultName;
    }
    
    // Split the arguments
    $args = explode(',', $matches[1]);
    
    // Clean up the argument
    if (isset($args[$index])) {
        $name = trim($args[$index]);
        // Remove any variable-specific noise
        $name = preg_replace('/\([^)]*\)|\{[^}]*\}|\[[^\]]*\]/', '', $name);
        return $name;
    }
    
    return $defaultName;
}

/**
 * Get a human-readable type for a variable
 *
 * @param mixed $var
 * @return string
 */
function getHumanReadableType(mixed $var): string
{
    if (is_null($var)) {
        return 'null';
    } elseif (is_array($var)) {
        return 'array:' . count($var);
    } elseif (is_object($var)) {
        return 'object:' . get_class($var);
    } elseif (is_bool($var)) {
        return 'boolean:' . ($var ? 'true' : 'false');
    } elseif (is_string($var)) {
        return 'string:' . strlen($var);
    } elseif (is_int($var)) {
        return 'int';
    } elseif (is_float($var)) {
        return 'float';
    } elseif (is_resource($var)) {
        return 'resource:' . get_resource_type($var);
    }
    
    return gettype($var);
}

/**
 * Format a variable with syntax highlighting for browser output
 *
 * @param mixed $var
 * @param int $depth
 * @param int $maxDepth
 * @return string
 */
function formatVar(mixed $var, int $depth = 0, int $maxDepth = 10): string
{
    if ($depth > $maxDepth) {
        return '<span class="null">*MAX DEPTH*</span>';
    }
    
    $output = '';
    
    if (is_null($var)) {
        $output .= '<span class="null">null</span>';
    } elseif (is_bool($var)) {
        $output .= '<span class="boolean">' . ($var ? 'true' : 'false') . '</span>';
    } elseif (is_string($var)) {
        $output .= '<span class="string">"' . htmlspecialchars($var) . '"</span>';
    } elseif (is_int($var) || is_float($var)) {
        $output .= '<span class="number">' . $var . '</span>';
    } elseif (is_array($var)) {
        $output .= '<span class="array">array:' . count($var) . ' [</span>';
        $indent = str_repeat('  ', $depth + 1);
        
        if (!empty($var)) {
            $output .= "\n";
            foreach ($var as $key => $value) {
                $output .= $indent . formatKey($key) . ' => ' . formatVar($value, $depth + 1, $maxDepth) . "\n";
            }
            $output .= str_repeat('  ', $depth);
        }
        
        $output .= '<span class="array">]</span>';
    } elseif (is_object($var)) {
        $className = get_class($var);
        $output .= '<span class="object">' . $className . ' {</span>';
        $indent = str_repeat('  ', $depth + 1);
        
        $reflection = new ReflectionObject($var);
        $properties = $reflection->getProperties();
        
        if (!empty($properties)) {
            $output .= "\n";
            foreach ($properties as $property) {
                $property->setAccessible(true);
                $propertyName = $property->getName();
                $propertyValue = $property->getValue($var);
                
                $visibility = $property->isPublic() ? 'public' : ($property->isProtected() ? 'protected' : 'private');
                $output .= $indent . '<span class="property">' . $visibility . ' ' . $propertyName . '</span> => ' . formatVar($propertyValue, $depth + 1, $maxDepth) . "\n";
            }
            $output .= str_repeat('  ', $depth);
        }
        
        $output .= '<span class="object">}</span>';
    } elseif (is_resource($var)) {
        $output .= 'resource:' . get_resource_type($var);
    } else {
        $output .= htmlspecialchars(var_export($var, true));
    }
    
    return $output;
}

/**
 * Format an array key with appropriate styling
 *
 * @param mixed $key
 * @return string
 */
function formatKey(mixed $key): string
{
    if (is_string($key)) {
        return '<span class="property">"' . htmlspecialchars($key) . '"</span>';
    } else {
        return '<span class="number">' . $key . '</span>';
    }
}