<?php
class compassFunctions {


/**
 * Implementation of hook_sassy_resolve_path_NAMESPACE().
 */
function sassy_compass_sassy_resolve_path_compass($filename, $syntax = 'scss') {

  // Check for compass installed as a Library, if not use ours.
  // The latest Compass build can be found at https://github.com/chriseppstein/compass
  $path = libraries_get_path('compass') . '/frameworks/compass';
  if (!file_exists($path)) {
    $path = drupal_get_path('module', 'sassy_compass');
  }

  if ($filename == '*') {
    $filename = 'compass';
  }

  $filename = str_replace(array('.scss', '.sass'), '', $filename);
  $split = explode('/', $filename);
  if ($split[0] != 'compass' && $split[0] != 'lemonade') {
    array_unshift($split, 'compass');
  }
  $last = array_pop($split) . '.scss';
  if (substr($last, 0, 1) != '_') {
    $last = '_' . $last;
  }
  array_unshift($split, 'stylesheets');
  array_unshift($split, $path);
  $filename = str_replace('/_', '/', implode('/', $split)) . '/' . $last;

  return $filename;
}

/**
 * Implementation of hook_sassy_functions().
 * Lists all functions defined by the Compass library.
 */
function sassy_compass_sassy_functions() {
  $functions  = 'if resolve-path ';
  $functions .= 'adjust-lightness scale-lightness adjust-saturation scale-saturation scale-color-value ';
  $functions .= 'is-position is-position-list opposite-position ';
  $functions .= '-webkit -moz -o -ms -svg -pie -css2 owg prefixed prefix ';
  $functions .= 'elements-of-type ';
  $functions .= 'enumerate ';
  $functions .= 'font-files ';
  $functions .= 'image-width image-height ';
  $functions .= 'inline-image inline-font-files ';
  $functions .= 'blank compact -compass-nth -compass-list -compass-list -compass-space-list -compass-list-size -compass-slice first-value-of ';
  $functions .= 'nest append-selector headers ';
  $functions .= 'pi sin cos tan ';
  $functions .= 'comma-list prefixed-for-transition ';
  $functions .= 'stylesheet-url font-url image-url';
  $output = array();
  $functions = explode(' ', $functions);
  foreach ($functions as $function) {
    $function = preg_replace('/[^a-z0-9_]/', '_', $function);
    $output[$function] = array(
      'name' => $function,
      'callback' => 'sassy_compass__' . $function,
    );
  }
  return $output;
}

/**
 * Defines the "if" function, used like: if(condition, if_true, if_false)
 */
function sassy_compass__if($condition, $if_true, $if_false) {
  return ($condition ? $if_true : $if_false);
}

/**
 * Resolves requires to the compass namespace (eg namespace/css3/border-radius)
 */
function sassy_compass__resolve_path($file) {
  if ($file{0} == '/') {
    return $file;
  }
  if (!$path = realpath($file)) {
    $path = SassScriptFunction::$context->node->token->filename;
    $path = substr($path, 0, strrpos($path, '/')) . '/';
    $path = $path . $file;
    $last = '';
    while ($path != $last) {
      $last = $path;
      $path = preg_replace('`(^|/)(?!\.\./)([^/]+)/\.\./`', '$1', $path);
    }
    $path = realpath($path);
  }
  if ($path) {
    return $path;
  }
  return false;
}

/**
 * Implements hook_sassy_css_preprocessor_settings_form_alter().
 * Adds the "Compass: Implicit compile" option.
 */
function sassy_compass_form_sassy_css_preprocessor_settings_form_alter(&$form, $form_state) {
  extract($form_state['prepro']);
  $local += array(
    'compass_implicit_include' => FALSE
  );

  $form['compass_implicit_include'] = array(
    '#type' => 'checkbox',
    '#title' => 'Include full Compass libraries in every file',
    '#description' => 'Should the use of Compass be implicit for every SASS/SCSS file? This will slow down compile time.',
    '#default_value' => $local['compass_implicit_include']
  );

  return $form;
}

/**
 * Adds the Compass libraries to all SASS files if the user selected this option in the Prepro admin screen.
 */
function sassy_compass_prepro_precompile_sass_alter(&$contents, $file, $local) {
  if (@$local['compass_implicit_include']) {
    if (strpos($contents, 'compass/compass') === FALSE && strpos($contents, 'compass/*') === FALSE) {
      $comment = '/* ' . t('Including Compass libraries via implicit include') . ' */';
      $contents = $comment . "\n@import 'compass/compass';\n\n" . $contents;
    }
  }
}

/**
 * Adds the Compass libraries to all SCSS files if the user selected this option in the Prepro admin screen.
 */
function sassy_compass_prepro_precompile_scss_alter(&$contents, $file, $local) {
  sassy_compass_prepro_precompile_sass_alter($contents, $file, $local);
}

function sassy_compass__comma_list() {
	print_r(func_get_args());
	die;
}

function sassy_compass__prefixed_for_transition($prefix, $property) {

}

function sassy_compass__blank($object) {
	if (is_object($object)) {
		$object =  $object->value;
	}
	$result = false;
	if (is_bool($object)) {
		$result = !$object;
	}
	if (is_string($object)) {
		$result = (strlen(trim($object, ' ,')) === 0);
	}

	return new SassBoolean($result);
}

function sassy_compass__compact() {
  $sep = ', ';

  $args = func_get_args();
  $list = array();

  // remove blank entries
  // append non-blank entries to list
  foreach ($args as $k=>$v) {
    if (is_object($v)) {
      $string = (isset($v->value) ? $v->value : FALSE);
    }
    else {
      $string = (string) $v;
    }
    if (empty($string) || $string == 'false') {
      unset($args[$k]);
      continue;
    }
    $list[] = $string;
  }
  return new SassString(implode($sep, $list));
}

function sassy_compass___compass_nth() {
	$args = func_get_args();
	$place = array_pop($args);
	$list = array();
	foreach ($args as $arg) {
		$list = array_merge($list, sassy_compass__list($arg));
	}

	if ($place == 'first') {
		$place = 0;
	}
	if ($place == 'last') {
		$place = count($list) - 1;
	}

	if (isset($list[$place])) {
		return current(SassScriptLexer::$instance->lex($list[$place], new SassContext()));
	}
	return new SassBoolean(false);
}

function sassy_compass___compass_list() {
	$args = func_get_args();
	$list = array();
	foreach ($args as $arg) {
		$list = array_merge($list, sassy_compass__list($arg));
	}
	return new SassString(implode(', ', $list));
}

function sassy_compass___compass_space_list() {
	$args = func_get_args();
	$list = sassy_compass__list($args, ',');
	return new SassString(implode(' ', $list));
}

function sassy_compass___compass_list_size() {
	$args = func_get_args();
	$list = sassy_compass__list($args, ',');
	return new SassNumber(count($list));
}

function sassy_compass___compass_list_slice($list, $start, $end) {
	$args = func_get_args();
	$end = array_pop($args);
	$start = array_pop($args);
	$list = sassy_compass__list($args, ',');
	return implode(',', array_slice($list, $start, $end));
}

function sassy_compass__first_value_of() {
	$args = array();
	$args[] = 'first';
	return call_user_func_array('sassy_compass___compass_nth', $args);
}

function sassy_compass__list($list, $seperator = ',') {
  if (is_object($list)) {
    $list = $list->value;
  }
  if (is_array($list)) {
  	$newlist = array();
  	foreach ($list as $listlet) {
  		$newlist = array_merge($newlist, sassy_compass__list($listlet, $seperator));
  	}
  	$list = implode(', ', $newlist);
  }

  $out = array();
  $size = 0;
  $braces = 0;
  $stack = '';
  for($i = 0; $i < strlen($list); $i++) {
    $char = substr($list, $i, 1);
    switch ($char) {
      case '(':
        $braces++;
        $stack .= $char;
        break;
      case ')':
        $braces--;
        $stack .= $char;
        break;
      case $seperator:
        if ($braces === 0) {
          $out[] = $stack;
          $stack = '';
          $size++;
          break;
        }

      default:
        $stack .= $char;
    }
  }
  $out[] = $stack;
  return $out;
}<?php

# Check if any of the arguments passed require a vendor prefix.
function sassy_compass__prefixed($prefix, $list) {
  $list = sassy_compass__list($list);
  $prefix = trim(preg_replace('/[^a-z]/', '', strtolower($prefix)));

  # thanks http://www.quirksmode.org/css/contents.html
  $reqs = array(
    'pie' => array(
      'border-radius', 'box-shadow', 'border-image', 'background', 'linear-gradient',
    ),
    'webkit' => array(
      'background-clip', 'background-origin', 'border-radius', 'box-shadow', 'box-sizing', 'columns',
      'gradient', 'linear-gradient', 'text-stroke'
    ),
    'moz' => array(
      'background-size', 'border-radius', 'box-shadow', 'box-sizing', 'columns', 'gradient', 'linear-gradient'
    ),
    'o' => array(
      'background-origin', 'text-overflow'
    ),
  );
  foreach ($list as $item) {
    $aspect = trim(current(explode('(', $item)));
    if (isset($reqs[$prefix]) && in_array($aspect, $reqs[$prefix])) {
      return new SassBoolean(TRUE);
    }
  }
  return new SassBoolean(FALSE);
}

function sassy_compass___webkit($input) {
  return sassy_compass__prefix('webkit', $input);
}
function sassy_compass___moz($input) {
  return sassy_compass__prefix('moz', $input);
}
function sassy_compass___o($input) {
  return sassy_compass__prefix('o', $input);
}
function sassy_compass___ms($input) {
  return sassy_compass__prefix('ms', $input);
}
function sassy_compass___svg($input) {
  return sassy_compass__prefix('ms', $input);
}
function sassy_compass___pie($input) {
  return sassy_compass__prefix('ms', $input);
}
function sassy_compass___css2($input) {
  return sassy_compass__prefix('ms', $input);
}
function sassy_compass___owg($input) {
  return sassy_compass__prefix('ms', $input);
}
function sassy_compass__prefix($vendor, $input) {
  if (is_object($vendor)) {
    $vendor = $vendor->value;
  }

  $list = sassy_compass__list($input, ',');
  $output = '';
  foreach($list as $key=>$value) {
    $list[$key] = '-' . $vendor . '-' . $value;
  }
  return new SassString(implode(', ', $list));
}<?php

function sassy_compass__image_width($file) {
  if ($info = sassy_compass__image_info($file)) {
    return new SassNumber($info[0] . 'px');
  }
  return new SassNumber('0px');
}

function sassy_compass__image_height($file) {
  if ($info = sassy_compass__image_info($file)) {
    return new SassNumber($info[1] . 'px');
  }
  return new SassNumber('0px');  
}

function sassy_compass__image_info($file) {
  if ($path = sassy_compass__resolve_path($file)) {
    if ($info = getimagesize($path)) {
      return $info;
    }
  }
  return false; 
}<?php

// http://compass-style.org/reference/compass/helpers/selectors/#nest
function sassy_compass__nest() {
	$args = func_get_args();
	$output = explode(',', array_pop($args));

	for ($i = count($args) - 1; $i >= 0; $i--) {
		$current = explode(',', $args[$i]);
		$size = count($output);
		foreach ($current as $selector) {
			for ($j = 0; $j < $size; $j++) {
				$output[] = trim($selector) . " " . trim($output[$j]);
			}
		}
		$output = array_slice($output, $size);
	}

	return new SassString(implode(', ', $output));
}

function sassy_compass__append_selector($initial, $new) {
	$list = explode(',', $initial);
	foreach ($list as $k => $selector) {
		$list[$k] = trim($selector) . $new;
	}
	return new SassString(implode(', ', $list));
}

function sassy_compass__headers($from = false, $to = false) {
	if (is_object($from))
		$from = $from->value;
	if (is_object($to))
		$to = $to->value;

	if (!$from || !is_numeric($from))
		$from = 1;
	if (!$to || !is_numeric($to))
		$to = 6;
	
	$from = (int) $from;
	$to = (int) $to;

	$output = array();
	for ($i = $from; $i <= $to; $i++) {
		$output[] = 'h' . $i;
	}
	return new SassString(implode(', ', $output));
}<?php

# not sure what should happen with these

function sassy_compass__stylesheet_url($path, $only_path = FALSE) {
	return sassy_compass__url($path, $only_path);
}

function sassy_compass__font_url($path, $only_path = FALSE) {
	return sassy_compass__url($path, $only_path);
}

function sassy_compass__image_url($path, $only_path = FALSE) {
	return sassy_compass__url($path, $only_path);
}

function sassy_compass__url($path, $only_path = FALSE, $web_path = TRUE) {
	$opath = $path;
	if (!$path = SassFile::get_file($path, SassParser::$instance, false)) {
		throw new Exception('File not found: ' . $opath);
	}

	$path = $path[0];
	if ($web_path) {
		$webroot = realpath($_SERVER['DOCUMENT_ROOT']);
		$path = str_replace($webroot, '', $path);
	}

	if ($only_path) {
		return new SassString($path);
	}
	return new SassString("url('$path')");
}

function sassy_compass__elements_of_type($type) {
  if (is_object($type)) {
    $type = $type->value;
  }
  $type = strtolower(trim($type));

  $types = array(
    'block' => 'address article aside blockquote center dir div dd details dl dt fieldset'
             . 'figcaption figure form footer frameset h1 h2 h3 h4 h5 h6 hr header hgroup'
             . 'isindex menu nav noframes noscript ol p pre section summary ul',
    'inline'=> 'a abbr acronym audio b basefont bdo big br canvas cite code command'
             . 'datalist dfn em embed font i img input keygen kbd label mark meter output'
             . 'progress q rp rt ruby s samp select small span strike strong sub'
             . 'sup textarea time tt u var video wbr',
    'table' => 'table',
    'list-item' => 'li',
    'table-row-group' => 'tbody',
    'table-header-group' => 'thead',
    'table-footer-group' => 'tfoot',
    'table-row' => 'tr',
    'table-cell' => 'th td',
    'html5-block' => 'article aside deatils figcaption figure footer header hgroup menu nav section summary',
    'html5-inline' => 'audio canvas command datalist embed keygen mark meter output progress rp rt ruby time video wbr',
    'html5' => 'article aside audio canvas command datalist details embed figcaption figure footer header hgroup keygen mark menu meter nav output progress rp rt ruby section summary time video wbr',
  );

  if (isset($types[$type])) {
    return new SassString(str_replace(' ', ',', $types[$type]));
  }
  throw new SassException('Elements-of-type does not support the type ' . $type);
}

function sassy_compass__inline_image($file, $mime = NULL) {
	if ($path = sassy_compass__url($file, true, false)) {
		$info = getimagesize($path);
		$mime = $info['mime'];
		$data = base64_encode(file_get_contents($path));
		# todo - do not return encoded if file size > 32kb
		return new SassString("url('data:$mime;base64,$data')");
	}
	return new SassString('');
}

function sassy_compass__inline_font_files($file) {
	$args = func_get_args();
	$files = array();
	$mimes = array(
		'otf' => 'font.opentype',
		'ttf' => 'font.truetype',
		'woff' => 'font.woff',
		'off' => 'font.openfont',
	);

	while (count($args)) {
		$path = sassy_compass__resolve_path(array_shift($args));
		$data = base64_encode(file_get_contents($path));
		$format = array_shift($args);

		$ext = array_pop(explode('.', $file));
		if (isset($mimes[$ext])) {
			$mime = $mimes[$ext];
		}
		else {
			continue;
		}

		$files[] = "url('data:$mime;base64,$data') format('$format')";
	}

	return new SassString(implode(', ', $files));
}
/**
 * Enumerate all options within a set. Deprecated in favor of @for, @extend
 * @example enumerate('.foo', 1, 3) => ".foo-1, .foo-2, .foo-3"
 */
function sassy_compass__enumerate($prefix, $from, $to, $sep = NULL) {
	$output = array();

	foreach (array('prefix', 'from', 'to', 'sep') as $var) {
		if (is_object($$var)) {
			$$var = $$var->value;
		}
	}

	if ($sep === NULL) {
		$sep = '-';
	}

	for ($i = $from; $i <= $to; $i++) {
		$output[] = $prefix . $sep. $i;
	}

	return new SassString(implode(', ', $output));
}<?php

function sassy_compass__is_position($position) {
	if (is_object($position)) {
		$position = $position->value;
	}
	return new SassBoolean(in_array($position, array('top', 'left', 'bottom', 'right')));
}

function sassy_compass__is_position_list($position) {
	$list = array();
	foreach (func_get_args() as $pos) {
		$list = array_merge($list, sassy_compass__list($pos, ' '));
	}
	foreach ($list as $el) {
		if (!in_array($el, array('top', 'left', 'bottom', 'right'))) {
			return new SassBoolean(FALSE);
		}
	}
	return new SassBoolean(TRUE);
}

# returns the opposite position of a side or corner.
function sassy_compass__opposite_position($position) {
	$list = sassy_compass__list($position, ' ');
	foreach ($list as $key=>$val) {
		switch ($val) {
			case 'top':
				$val = 'bottom';
				break;
			case 'bottom':
				$val = 'top';
				break;
			case 'left':
				$val = 'right';
				break;
			case 'right':
				$val = 'left';
				break;
		}
		$list[$key] = $val;
	}
	return implode(' ', $list);
}


# TODO<?php

/**
 * A genericized version of lighten/darken so negative values can be used
 * @param SassColour $color - the color to adjust
 * @param SassNumber $amount  the value to adjust by
 */
function sassy_compass__adjust_lightness($color, $amount) {
	return sassy_compass__adjust_color_value($color, 'lightness', $amount);
}

/**
 * Scales a color's lightness by some percentage.
 * If the amount is negative, the color is scaled darker, if positive, it is scaled lighter.
 * This will never return a pure light or dark color unless the amount is 100%.
 */
function sassy_compass__scale_lightness($color, $amount) {
	return sassy_compass__scale_color_value($color, 'lightness', $amount);
}

/**
 * A genericized version of saturate/desaturate so negative values can be used
 * @param SassColour $color - the color to adjust
 * @param SassNumber $amount  the value to adjust by
 */
function sassy_compass__adjust_saturation($color, $amount) {
	return sassy_compass__adjust_color_value($color, 'saturation', $amount);
}

/**
 * Scales a color's lightness by some percentage.
 * If the amount is negative, the color is scaled darker, if positive, it is scaled lighter.
 * This will never return a pure light or dark color unless the amount is 100%.
 */
function sassy_compass__scale_saturation($color, $amount) {
	return sassy_compass__scale_color_value($color, 'saturation', $amount);
}

function sassy_compass__adjust_color_value($color, $attribute, $amount) {
	if (!is_object($color)) {
		$color = new SassColour($color);
	}
	if (is_object($amount)) {
		$amount = $amount->value;
	}
	$amount = preg_replace('/[^0-9\.\-]/', '', $amount);

	// ensure we have all attributes;
	$color->getRgb();
	$color->getHsl();
	$value = $color->$attribute;

	$color->$attribute = $value + $amount;

	// ensure conversion took place...
	switch ($attribute) {
		case 'red':
		case 'green':
		case 'blue':
			$color->rgb2hsl();
		default:
			$color->hsl2rgb();
	}
	return $color;
}

function sassy_compass__scale_color_value($color, $attribute, $amount) {
	if (!is_object($color)) {
		$color = new SassColour($color);
	}
	if (is_object($amount)) {
		$amount = $amount->value;
	}
	$amount = preg_replace('/[^0-9\.\-]/', '', $amount);

	// ensure we have all attributes;
	$color->getRgb();
	$color->getHsl();
	$value = $color->$attribute;

	$color->$attribute = ($amount > 0) ? $value + (100 - $value) * ($amount / 100) : $value + ($value * $amount / 100);

	// ensure conversion took place...
	switch ($attribute) {
		case 'red':
		case 'green':
		case 'blue':
			$color->rgb2hsl();
		default:
			$color->hsl2rgb();
	}
	return $color;
}

function sassy_compass__pi() {
	return pi();
}

function sassy_compass__sin($number) {
	return new SassNumber(sin($number));
}

function sassy_compass__cos($number) {
	return new SassNumber(sin($number));
}

function sassy_compass__tan($number) {
	return new SassNumber(sin($number));
}<?php
// http://compass-style.org/reference/compass/helpers/font-files/#font-files
function sassy_compass__font_files() {
  $font_types = array(
    'woff' => 'woff',
    'otf' => 'opentype',
    'opentype' => 'opentype',
    'ttf' => 'truetype',
    'truetype' => 'truetype',
    'svg' => 'svg',
    'eot' => 'embedded-opentype',
  );

  $args = func_get_args();
  foreach ($args as $k=>$v) {
    if (is_object($v)) {
      $args[$k] = $v->value;
    }
  }

  $output = array();
  while (count($args)) {
    $url = array_shift($args);

    preg_match('/\.([a-z-]+).*$/i', $url, $type);
    $type = $type[1];

    if (!isset($font_types[$type])) {
      throw new SassException('Could not determine the font type for ' . $url);
    }

    $output[] = "font_url($url) format({$font_types[$type]})";
  }

  return new SassString(implode(', ', $output));
}
}
