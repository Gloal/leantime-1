<?php

/**
 * Template class - Template routing
 *
 */

namespace leantime\core;

use leantime\domain\models\auth\roles;
use leantime\domain\services;

/**
 * Template class - Template routing
 *
 * @package leantime
 * @subpackage core
 */
class template
{
    use eventhelpers;

    /**
     * @var array - vars that are set in the action
     */
    private $vars = array();

    /**
     *
     * @var string
     */
    private $frontcontroller = '';

    /**
     * @var string
     */
    private $notifcation = '';

    /**
     * @var string
     */
    private $notifcationType = '';

    /**
     * @var string
     */
    private $hookContext = '';

    /**
     * @var string
     */
    public $tmpError = '';

    /**
     * @var IncomingRequest|string
     */
    public $incomingRequest = '';

    /**
     * @var language|string
     */
    public $language = '';

    /**
     * @var string
     */
    public $template = '';

    /**
     * @var array
     */
    public $picture = array(
        'calendar' => 'fa-calendar',
        'clients' => 'fa-people-group',
        'dashboard' => 'fa-th-large',
        'files' => 'fa-picture',
        'leads' => 'fa-signal',
        'messages' => 'fa-envelope',
        'projects' => 'fa-bar-chart',
        'setting' => 'fa-cogs',
        'tickets' => 'fa-pushpin',
        'timesheets' => 'fa-table',
        'users' => 'fa-people-group',
        'default' => 'fa-off',
    );

    /**
     * @var theme
     */
    private theme $theme;

    /**
     * __construct - get instance of frontcontroller
     *
     * @param  theme $theme
     * @param  language $language
     * @param  frontcontroller $frontcontroller
     * @param  IncomingRequest $incomingRequest
     * @param  environment $config
     * @param  appSettings $settings
     * @param  services\auth $login
     * @param  roles $roles
     * @access public
     * @return self
     */
    public function __construct(
        theme $theme,
        language $language,
        frontcontroller $frontcontroller,
        IncomingRequest $incomingRequest,
        environment $config,
        appSettings $settings,
        services\auth $login,
        roles $roles
    ) {
        $this->theme = $theme;
        $this->language = $language;
        $this->frontcontroller = $frontcontroller;
        $this->incomingRequest = $incomingRequest;
        $this->config = $config;
        $this->settings = $settings;
        $this->login = $login;
        $this->roles = $roles;
    }

    /**
     * assign - assign variables in the action for template
     *
     * @param $name
     * @param $value
     * @return void
     */
    public function assign(string $name, mixed $value): void
    {
        $value = self::dispatch_filter("var.$name", $value);

        $this->vars[$name] = $value;
    }

        /**
         * setNotification - assign errors to the template
         *
         * @param  string $msg
         * @param  string $type
         * @param  string $event_id as a string for further identification
         * @return string
         */
        public function setNotification(string $msg, string $type, string $event_id = ''): void
        {

            $_SESSION['notification'] = $msg;
            $_SESSION['notifcationType'] = $type;
            $_SESSION['event_id'] = $event_id;
        }

    /**
     * getTemplatePath - Find template in custom and src directories
     *
     * @access public
     * @param  string $module Module template resides in
     * @param  string $name   Template filename name (including tpl.php extension)
     * @return string|boolean Full template path or false if file does not exist
     */
    public function getTemplatePath(string $module, string $name): string|false
    {
        if ($module == '' || $name == '') {
            return false;
        }

        $plugin_path = self::dispatch_filter('relative_plugin_template_path', '', [
                    'module' => $module,
                    'name' => $name,
        ]);

        if ($_SESSION['isInstalled'] === true && $_SESSION['isUpdated'] === true) {
            $pluginService = app()->make(services\plugins::class);
        }

        if (empty($plugin_path) || !file_exists($plugin_path)) {
            $file = '/' . $module . '/templates/' . $name;

            if (file_exists(ROOT . '/../app/custom' . $file) && is_readable(ROOT . '/../app/custom' . $file)) {
                return ROOT . '/../app/custom' . $file;
            }

            if (file_exists(ROOT . '/../app/plugins' . $file) && is_readable(ROOT . '/../app/plugins' . $file)) {
                if ($_SESSION['isInstalled'] === true && $_SESSION['isUpdated'] === true && $pluginService->isPluginEnabled($module)) {
                    return ROOT . '/../app/plugins' . $file;
                }
            }

            if (file_exists(ROOT . '/../app/domain' . $file) && is_readable(ROOT . '/../app/domain/' . $file)) {
                return ROOT . '/../app/domain/' . $file;
            }

            return false;
        }

        if (file_exists(ROOT . '/../app/custom' . $plugin_path) && is_readable(ROOT . '/../app/custom' . $plugin_path)) {
            return ROOT . '/../app/custom' . $plugin_path;
        }

        if (file_exists(ROOT . '/../app/plugins' . $plugin_path) && is_readable(ROOT . '/../app/plugins' . $plugin_path)) {
            if ($_SESSION['isInstalled'] === true && $_SESSION['isUpdated'] === true && $pluginService->isPluginEnabled($module)) {
                return ROOT . '/../app/plugins' . $plugin_path;
            }
        }

        if (file_exists(ROOT . '/../app/domain' . $plugin_path) && is_readable(ROOT . '/../app/domain/' . $plugin_path)) {
            return ROOT . '/../app/domain/' . $plugin_path;
        }

        return false;
    }

    /**
     * display - display template from folder template including main layout wrapper
     *
     * @access public
     * @param  $template
     * @return void
     */
    public function display(string $template, string $layout = "app")
    {
        //These variables are available in the template
        $config = $this->config;
        $settings = $this->settings;
        $login = $this->login;
        $roles = $this->roles;
        $language = $this->language;

        $template = self::dispatch_filter('template', $template);
        $template = self::dispatch_filter("template.$template", $template);

        $this->template = $template;

        //Load Layout file
        $layout = htmlspecialchars($layout);

        $layout = self::dispatch_filter('layout', $layout);
        $layout = self::dispatch_filter("layout.$template", $layout);

        $layoutFilename = $this->theme->getLayoutFilename($layout . '.php', $template);

        if ($layoutFilename === false) {
            $layoutFilename = $this->theme->getLayoutFilename('app.php');
        }

        if ($layoutFilename === false) {
            die("Cannot find default 'app.php' layout file");
        }

        ob_start();

        require($layoutFilename);

        $layoutContent = ob_get_clean();

        $layoutContent = self::dispatch_filter('layoutContent', $layoutContent);
        $layoutContent = self::dispatch_filter("layoutContent.$template", $layoutContent);

        //Load Template
        //frontcontroller splits the name (actionname.modulename)
        $action = $this->frontcontroller::getActionName($template);
        $module = $this->frontcontroller::getModuleName($template);

        $loadFile = $this->getTemplatePath($module, $action . '.tpl.php');

        $this->hookContext = "tpl.$module.$action";

        ob_start();

        if ($loadFile !== false) {
            require_once($loadFile);
        } else {
            error_log("Tried loading " . $loadFile . ". File not found");
            $this->frontcontroller::redirect(BASE_URL . "/error/error404");
        }

        $content = ob_get_clean();

        $content = self::dispatch_filter('content', $content);
        $content = self::dispatch_filter("content.$template", $content);

        //Load template content into layout content
        $render = str_replace("<!--###MAINCONTENT###-->", $content, $layoutContent);

        $render = self::dispatch_filter('render', $render);
        $render = self::dispatch_filter("render.$template", $render);

        echo $render;
    }

    /**
     * displayJson - returns json data
     *
     * @access public
     * @param  $jsonContent
     * @return void
     */
    public function displayJson($jsonContent)
    {
        header('Content-Type: application/json; charset=utf-8');
        if ($jsonContent !== false) {
            echo $jsonContent;
        } else {
            echo json_encode(['error' => 'Invalid Json']);
        }
    }

    /**
     * display - display only the template from the template folder without a wrapper
     *
     * @access public
     * @param  $template
     * @return void
     */
    public function displayPartial($template)
    {
        $this->display($template, 'blank');
    }

    /**
     * get - get assigned values
     *
     * @access public
     * @param  $name
     * @return array
     */
    public function get(string $name): mixed
    {
        if (!isset($this->vars[$name])) {
            return null;
        }

        return $this->vars[$name];
    }

    /**
     * getNotification - pulls notification from the current session
     *
     * @access public
     * @return array
     */
    public function getNotification(): array
    {
        if (isset($_SESSION['notifcationType']) && isset($_SESSION['notification'])) {
            if(isset($_SESSION['event_id'])) {
                $event_id = $_SESSION['event_id'];
            }else{
                $event_id='';
            }
            return array('type' => $_SESSION['notifcationType'], 'msg' => $_SESSION['notification'], 'event_id' => $event_id);
        } else {
            return array('type' => "", 'msg' => "", 'event_id' => "");
        }
    }

    /**
     * displaySubmodule - display a submodule for a given module
     *
     * @access public
     * @param  $alias
     * @return void
     */
    public function displaySubmodule($alias)
    {
        $frontController = $this->frontcontroller;
        $config = $this->config;
        $settings = $this->settings;
        $login = $this->login;
        $roles = $this->roles;

        $submodule = array("module" => '', "submodule" => '');

        $aliasParts = explode("-", $alias);
        if (count($aliasParts) > 1) {
            $submodule['module'] = $aliasParts[0];
            $submodule['submodule'] = $aliasParts[1];
        }

        $relative_path = self::dispatch_filter(
            'submodule_relative_path',
            "{$submodule['module']}/templates/submodules/{$submodule['submodule']}.sub.php",
            [
                'module' => $submodule['module'],
                'submodule' => $submodule['submodule'],
            ]
        );

        $file = "../custom/domain/$relative_path";

        if (!file_exists($file)) {
            $file = "../app/domain/$relative_path";
        }

        if (file_exists($file)) {
            include $file;
        }
    }

    /**
     * displayNotification - display notification
     *
     * @access public
     * @return string
     */
    public function displayNotification()
    {
        $notification = '';
        $note = $this->getNotification();
        $language = $this->language;
        $message_id = $note['msg'];

        $message = self::dispatch_filter(
            'message',
            $language->__($message_id, false),
            $note
        );
        $message = self::dispatch_filter(
            "message_{$message_id}",
            $message,
            $note
        );

        if (!empty($note) && $note['msg'] != '' && $note['type'] != '') {
            $notification = '<script type="text/javascript">jQuery.growl({message: "'
                . $message . '", style: "' . $note['type'] . '"});</script>';

            self::dispatch_event("notification_displayed", $note);

            $_SESSION['notification'] = "";
            $_SESSION['notificationType'] = "";
            $_SESSION['event_id'] = "";
        }

        return $notification;
    }

    /**
     * displayInlineNotification - display notification
     *
     * @access public
     * @return string
     */
    public function displayInlineNotification()
    {

        $notification = '';
        $note = $this->getNotification();
        $language = $this->language;
        $message_id = $note['msg'];

        $message = self::dispatch_filter(
            'message',
            $language->__($message_id, false),
            $note
        );
        $message = self::dispatch_filter(
            "message_{$message_id}",
            $message,
            $note
        );

        if (!empty($note) && $note['msg'] != '' && $note['type'] != '') {
            $notification = "<div class='inputwrapper login-alert login-" . $note['type'] . "' style='position: relative;'>
                                <div class='alert alert-" . $note['type'] . "' style='padding:15px;' >
                                    <strong>" . $message . "</strong>
                                </div>
                            </div>
                            ";

            self::dispatch_event("notification_displayed", $note);

            $_SESSION['notification'] = "";
            $_SESSION['notificationType'] = "";
            $_SESSION['event_id'] = "";
        }

        return $notification;
    }

    /**
     * redirect - redirect to a given url
     *
     * @param  string $url
     * @return void
     */
    public function redirect(string $url): void
    {
        header("Location:" . trim($url));
        exit();
    }

    /**
     * getSubdomain - get subdomain from url
     *
     * @return string
     */
    public function getSubdomain(): string
    {
        preg_match('/(?:http[s]*\:\/\/)*(.*?)\.(?=[^\/]*\..{2,5})/i', $_SERVER['HTTP_HOST'], $match);

        $domain = $_SERVER['HTTP_HOST'];
        $tmp = explode('.', $domain); // split into parts
        $subdomain = $tmp[0];

        return $subdomain;
    }

    /**
     * __ - returns a language specific string. wraps language class method
     *
     * @param  string $index
     * @return string
     */
    public function __(string $index): string
    {
        return $this->language->__($index);
    }

    /**
     * e - echos and escapes content
     *
     * @param  ?string $content
     * @return void
     */
    public function e(?string $content): void
    {
        $content = $this->convertRelativePaths($content);
        $escaped = $this->escape($content);

        echo $escaped;
    }

    /**
     * escape - escapes content
     *
     * @param  ?string $content
     * @return string
     */
    public function escape(?string $content): string
    {
        if (!is_null($content)) {
            $content = $this->convertRelativePaths($content);
            return htmlentities($content);
        }

        return '';
    }

    /**
     * escapeMinimal - escapes content
     *
     * @param  ?string $content
     * @return string
     */
    public function escapeMinimal(?string $content): string
    {
        $content = $this->convertRelativePaths($content);
        $config = array(
            'safe' => 1,
            'style_pass' => 1,
            'cdata' => 1,
            'comment' => 1,
            'deny_attribute' => '* -href -style',
            'keep_bad' => 0,
        );

        if (!is_null($content)) {
            return htmLawed($content, array(
                'comments' => 0,
                'cdata' => 0,
                'deny_attribute' => 'on*',
                'elements' => '* -applet -canvas -embed -object -script',
                'schemes' => 'href: aim, feed, file, ftp, gopher, http, https, irc, mailto, news, nntp, sftp, ssh, tel, telnet; style: !; *:file, http, https',
            ));
        }

        return '';
    }

    /**
     * getFormattedDateString - returns a language specific formatted date string. wraps language class method
     *
     * @access public
     * @param  string $date
     * @return string
     */
    public function getFormattedDateString($date): string
    {
        return $this->language->getFormattedDateString($date);
    }

    /**
     * getFormattedTimeString - returns a language specific formatted time string. wraps language class method
     *
     * @access public
     * @param $date string
     * @return string
     */
    public function getFormattedTimeString($date): string
    {
        return $this->language->getFormattedTimeString($date);
    }

    /**
     * getFormattedDateTimeString - returns a language specific formatted date and time string. wraps language class method
     *
     * @access public
     * @param  string $date
     * @return string
     */
    public function get24HourTimestring(string $dateTime): string
    {
        return $this->language->get24HourTimestring($dateTime);
    }

    /**
     * truncate - truncate text
     *
     * @see https://stackoverflow.com/questions/1193500/truncate-text-containing-html-ignoring-tags
     * @author Søren Løvborg <https://stackoverflow.com/users/136796/s%c3%b8ren-l%c3%b8vborg>
     * @access public
     * @param  string $html
     * @param  int $maxLength
     * @param  string $ending
     * @param  bool $exact
     * @param  bool $considerHtml
     * @return string
     */
    public function truncate($html, $maxLength = 100, $ending = '(...)', $exact = true, $considerHtml = false)
    {
        $printedLength = 0;
        $position = 0;
        $tags = array();
        $isUtf8 = true;
        $truncate = "";
        $html = $this->convertRelativePaths($html);
        // For UTF-8, we need to count multibyte sequences as one character.
        $re = $isUtf8 ? '{</?([a-z]+)[^>]*>|&#?[a-zA-Z0-9]+;|[\x80-\xFF][\x80-\xBF]*}' : '{</?([a-z]+)[^>]*>|&#?[a-zA-Z0-9]+;}';

        while ($printedLength < $maxLength && preg_match($re, $html, $match, PREG_OFFSET_CAPTURE, $position)) {
            list($tag, $tagPosition) = $match[0];

            // Print text leading up to the tag.
            $str = substr($html, $position, $tagPosition - $position);
            if ($printedLength + strlen($str) > $maxLength) {
                $truncate .= substr($str, 0, $maxLength - $printedLength);
                $printedLength = $maxLength;
                break;
            }

            $truncate .= $str;
            $printedLength += strlen($str);
            if ($printedLength >= $maxLength) {
                break;
            }

            if ($tag[0] == '&' || ord($tag) >= 0x80) {
                // Pass the entity or UTF-8 multibyte sequence through unchanged.
                $truncate .= $tag;
                $printedLength++;
            } else {
                // Handle the tag.
                $tagName = $match[1][0];
                if ($tag[1] == '/') {
                    // This is a closing tag.

                    $openingTag = array_pop($tags);
                    assert($openingTag == $tagName); // check that tags are properly nested.

                    $truncate .= $tag;
                } elseif ($tag[strlen($tag) - 2] == '/') {
                    // Self-closing tag.
                    $truncate .= $tag;
                } else {
                    // Opening tag.
                    $truncate .= $tag;
                    $tags[] = $tagName;
                }
            }

            // Continue after the tag.
            $position = $tagPosition + strlen($tag);
        }

        // Print any remaining text.
        if ($printedLength < $maxLength && $position < strlen($html)) {
            $truncate .= sprintf(substr($html, $position, $maxLength - $printedLength));
        }

        // Close any open tags.
        while (!empty($tags)) {
            $truncate .= sprintf('</%s>', array_pop($tags));
        }

        if (strlen($truncate) >= $maxLength) {
            $truncate .= $ending;
        }

        return $truncate;
    }

    /**
     * convertRelativePaths - convert relative paths to absolute paths
     *
     * @access public
     * @param  ?string $text
     * @return string
     */
    public function convertRelativePaths(?string $text)
    {
        if (is_null($text)) {
            return $text;
        }

        $base = BASE_URL;

        // base url needs trailing /
        if (substr($base, -1, 1) != "/") {
            $base .= "/";
        }

        // Replace links
        $pattern = "/<a([^>]*) " .
                "href=\"([^http|ftp|https|mailto|#][^\"]*)\"/";
        $replace = "<a\${1} href=\"" . $base . "\${2}\"";
        $text = preg_replace($pattern, $replace, $text);

        // Replace images
        $pattern = "/<img([^>]*) " .
                "src=\"([^http|ftp|https][^\"]*)\"/";
        $replace = "<img\${1} src=\"" . $base . "\${2}\"";

        $text = preg_replace($pattern, $replace, $text);
        // Done
        return $text;
    }

    /**
     * getModulePicture - get module picture
     *
     * @access public
     * @return string
     */
    public function getModulePicture()
    {
        $module = frontcontroller::getModuleName($this->template);

        $picture = $this->picture['default'];
        if (isset($this->picture[$module])) {
            $picture = $this->picture[$module];
        }

        return $picture;
    }

    /**
     * displayLink - display link
     *
     * @access public
     * @param  string $module
     * @param  string $name
     * @param  ?array $params
     * @param  ?array $attribute
     * @return string
     */
    public function displayLink($module, $name, $params = null, $attribute = null)
    {

        $mod = explode('.', $module);

        if (is_array($mod) === true && count($mod) == 2) {
            $action = $mod[1];
            $module = $mod[0];

            $mod = $module . '/class.' . $action . '.php';
        } else {
            $mod = array();
            return false;
        }

        $returnLink = false;

        $url = "/" . $module . "/" . $action . "/";

        if (!empty($params)) {
            foreach ($params as $key => $value) {
                $url .= $value . "/";
            }
        }

        $attr = '';

        if ($attribute != null) {
            foreach ($attribute as $key => $value) {
                $attr .= $key . " = '" . $value . "' ";
            }
        }

        $returnLink = "<a href='" . BASE_URL . "" . $url . "' " . $attr . ">" . $name . "</a>";

        return $returnLink;
    }

    /**
    * patchDownloadUrlToFilenameOrAwsUrl - Replace all local download.php references in <img src=""> tags
    * by either local filenames or AWS URLs that can be accesse without being authenticated
    *
    * Note: This patch is required by the PDF generating engine as it retrieves URL data without being
    * authenticated
    *
    * @access public
    * @param  string  $textHtml HTML text, potentially containing <img srv="https://local.domain/download.php?xxxx"> tags
    * @return string  HTML text with the https://local.domain/download.php?xxxx replaced by either full qualified
    *                 local filenames or AWS URLs
    */

    public function patchDownloadUrlToFilenameOrAwsUrl(string $textHtml): string
    {
        $patchedTextHtml = $this->convertRelativePaths($textHtml);

        // TO DO: Replace local download.php
        $patchedTextHtml = $patchedTextHtml;

        return $patchedTextHtml;
    }

    /**
     * @param string $hookName
     * @param mixed  $payload
     */
    private function dispatchTplEvent($hookName, $payload = [])
    {
        $this->dispatchTplHook('event', $hookName, $payload);
    }

    /**
     * @param string $hookName
     * @param mixed  $payload
     * @param mixed  $available_params
     *
     * @return mixed
     */
    private function dispatchTplFilter($hookName, $payload = [], $available_params = [])
    {
        return $this->dispatchTplHook('filter', $hookName, $payload, $available_params);
    }

    /**
     * @param string $type
     * @param string $hookName
     * @param mixed  $payload
     * @param mixed  $available_params
     *
     * @return null|mixed
     */
    private function dispatchTplHook($type, $hookName, $payload = [], $available_params = [])
    {
        if (
            !is_string($type) || !in_array($type, ['event', 'filter'])
        ) {
            return;
        }

        if ($type == 'filter') {
            return self::dispatch_filter($hookName, $payload, $available_params, $this->hookContext);
        }

        self::dispatch_event($hookName, $payload, $this->hookContext);
    }
}
