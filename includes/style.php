<?php
/**
*
* @package Kleeja
* @version $Id: style.php 2026 2012-10-02 15:13:05Z saanina $
* @copyright (c) 2007 Kleeja.com
* @license ./docs/license.txt
*
*/


//no for directly open
if (!defined('IN_COMMON'))
{
	exit();
}

class kleeja_style
{
	var $vars; //Reference to $GLOBALS
	var $HTML; //html page content
	var $loop	= array();
	var $reg	= array('var' => '/([{]{1,2})+([A-Z0-9_\.]+)[}]{1,2}/i');
	var $caching = true; //save templates as caches to not compliled alot of times
		
        //Function to load a template file.
        function _load_template($template_name)
		{
			global $config, $THIS_STYLE_PATH_ABS, $STYLE_PATH_ADMIN_ABS, $DEFAULT_PATH_ADMIN_ABS;

			$is_admin_template = false;
			$style_path = $THIS_STYLE_PATH_ABS;
			
			//admin template always begin with admin_
			if(substr($template_name, 0, 6) == 'admin_')
			{
				$style_path =  $STYLE_PATH_ADMIN_ABS;
				$is_admin_template = true;
			}

			$template_path = $style_path . $template_name . '.html';

			//if template not found and default style is there and not admin tpl
			$is_tpl_exist = file_exists($template_path);
			if(!$is_tpl_exist) 
			{
				if(trim($config['style_depend_on']) != '')
				{
					$template_path_alternative = str_replace('/' . $config['style'] . '/', '/' . $config['style_depend_on'] . '/', $template_path);
					if(file_exists($template_path_alternative))
					{
						$template_path = $template_path_alternative;
						$is_tpl_exist = true;
					}
				}
				else if($is_admin_template)
				{
					$template_path = $DEFAULT_PATH_ADMIN_ABS . $template_name . '.html';
					$is_tpl_exist = true;
				}
				else if($config['style'] != 'default' && !$is_admin_template)
				{
					$template_path_alternative = str_replace('/' . $config['style'] . '/', '/default/', $template_path);
					if(file_exists($template_path_alternative))
					{
						$template_path = $template_path_alternative;
						$is_tpl_exist = true;
					}
				}
			}

			if(!$is_tpl_exist)
			{
				big_error('No Template !', 'Requested "' . $template_path . '" template doesnt exists or an empty !! ');
			}

			$this->HTML = file_get_contents($template_path);
			$this->_parse($this->HTML);
			//use 'b' to force binary mode
			if($filename = @fopen(PATH . 'cache/tpl_' . $this->re_name_tpl($template_name) . '.php', 'wb'))
			{
				@flock($filename, LOCK_EX);
				@fwrite($filename, $this->HTML);
				@flock($filename, LOCK_UN);
				@fclose($filename);
				// Read and write for owner, read for everybody else
				@chmod(PATH . 'cache/tpl_' . $this->re_name_tpl($template_name) . '.php', 0644);
			}
        }

        //Function to parse the Template Tags
        function _parse()
		{
			$this->HTML = preg_replace(array('#<([\?%])=?.*?\1>#s', '#<script\s+language\s*=\s*(["\']?)php\1\s*>.*?</script\s*>#s', '#<\?php(?:\r\n?|[ \n\t]).*?\?>#s'), '', $this->HTML);
            $this->HTML = preg_replace_callback('/\(([{A-Z0-9_\.}\s!=<>]+)\?(.*):(.*)\)/iU',array('kleeja_style','_iif_callback'), $this->HTML);
            $this->HTML = preg_replace_callback('/<(IF|ELSEIF) (.+)>/iU',array('kleeja_style','_if_callback'), $this->HTML);
            $this->HTML = preg_replace_callback('/<LOOP\s+NAME\s*=\s*(\"|)+([a-z0-9_\.]{1,})+(\"|)\s*>/i',array('kleeja_style','_loop_callback'), $this->HTML);
            $this->HTML = preg_replace_callback(kleeja_style::reg('var'),array('kleeja_style','_vars_callback'), $this->HTML);

            $rep = array(
						'/<\/(LOOP|IF|END|IS_BROWSER)>/i' => "<?php } ?>", 
						'/<INCLUDE(\s+NAME|)\s*=*\s*"(.+)"\s*>/iU' => '<?php echo $this->display("\\2"); ?>',
						'/<IS_BROWSER\s*=\s*"([a-z0-9,]+)"\s*>/iU' => '<?php if(is_browser("\\1")){ ?>',
						'/<IS_BROWSER\s*\!=\s*"([a-z0-9,]+)"\s*>/iU' => '<?php if(!is_browser("\\1")){ ?>',
						'/(<ELSE>|<ELSE \/>)/i' => '<?php }else{ ?>',
						'/<ODD\s*=\s*"([a-zA-Z0-9_\-\+\.\/]+)"\s*>(.*?)<\/ODD\>/is' => "<?php if(intval(\$value['\\1'])%2){?> \\2 <?php } ?>",
						'/<EVEN\s*=\s*"([a-zA-Z0-9_\-\+\.\/]+)"\s*>(.*?)<\/EVEN>/is' => "<?php if(intval(\$value['\\1'])% 2 == 0){?> \\2 <?php } ?>",
						'/<RAND\s*=\s*"(.*?)\"\s*,\s*"(.*?)"\s*>/is' => "<?php \$KLEEJA_tpl_rand_is=(!isset(\$KLEEJA_tpl_rand_is) || \$KLEEJA_tpl_rand_is==0)?1:0; print((\$KLEEJA_tpl_rand_is==1) ?'\\1':'\\2'); ?>",
						'/\{%(key|value)%\}/i' => '<?php echo $\\1; ?>',
				);

            $this->HTML = preg_replace(array_keys($rep), array_values($rep), $this->HTML);
        }
		
		//loop tag
		function _loop_callback($matches)
		{
			$var = (strpos($matches[2], '.') !== false) ?  str_replace('.', '"]["', $matches[2]) : $matches[2];
			return '<?php foreach($this->vars["' . $var . '"] as $key=>$value){ ?>';
		}

        //if tag
        function _if_callback($matches)
		{
            $char  = array(' eq ',' lt ',' gt ',' lte ',' gte ', ' neq ', '==', '!=', '>=', '<=', '<', '>');
            $reps  = array('==','<','>','<=','>=', '!=', '==', '!=', '>=', '<=', '<', '>');
            $atts = call_user_func(array('kleeja_style','_get_attributes'), $matches[0]);
            $con = !empty($atts['NAME']) ? $atts['NAME'] : (empty($atts['LOOP']) ? null : $atts['LOOP']);

            if(preg_match('/(.*)(' . implode('|', $char) . ')(.*)/i', trim($con), $arr))
			{
				$arr[1] = trim($arr[1]);
				$var1 = $arr[1][0] != '$' ? call_user_func(array('kleeja_style', '_var_callback'), (!empty($atts['NAME']) ? '{' . $arr[1] . '}' : '{{' . $arr[1] . '}}')) : $arr[1];
                $opr = str_replace($char, $reps, $arr[2]);
                $var2 = trim($arr[3]);

				//check for type 
				if($var2[0] != '$' && !preg_match('/[0-9]/', $var2))
				{
					$var2 = '"' . str_replace('"', '\"', $var2) . '"';
				}

                $con = "$var1$opr$var2";
            }
			elseif($con[0] !== '$')
			{
                $con = call_user_func(array('kleeja_style', '_var_callback'), (!empty($atts['NAME']) ? '{' . $con . '}' : '{{' . $con . '}}'));
            }

            return strtoupper($matches[1]) == 'IF' ?  '<?php if(' . $con . '){ ?>' : '<?php }elseif(' . $con . '){ ?>';
        }
		
        //iif tag
        function _iif_callback($matches)
		{
            return '<IF NAME="' . $matches[1] . '">' . $matches[2] . '<ELSE>' . $matches[3] . '</IF>';
        }
		
		
        //make variable printable
        function _vars_callback($matches)
		{
            return('<?php echo ' . call_user_func(array('kleeja_style', '_var_callback'), $matches) . '?>');
        }
		
        //variable replace
        function _var_callback($matches)
		{
            if(!is_array($matches))
			{
                preg_match(kleeja_style::reg('var'), $matches, $matches);
            }

			$var = !empty($matches[2]) ? str_replace('.', '\'][\'', $matches[2]) : '';
            return (!empty($matches[1]) && trim($matches[1]) == '{{') ? '$value[\'' . $var . '\']' : '$this->vars[\'' . $var . '\']';
        }
		
        //att variable replace
        function _var_callback_att($matches)
		{
            return trim($matches[1]) == '{' ? $this->_var_callback($matches) : '{' . $this->_var_callback($matches) . '}';
        }

        //get reg var
        function reg($var)
		{
            $vars = get_class_vars(__CLASS__);
            return($vars['reg'][$var]);
        }

        //get tag  attributes
        function _get_attributes($tag)
		{
            preg_match_all('/([a-z]+)="(.+)"/iU',$tag, $attribute);
			
			$attributes = array();
			
            for($i=0;$i<count($attribute[1]);$i++)
			{
                $att = strtoupper($attribute[1][$i]);
				
                if(preg_match('/NAME|LOOP/',$att))
				{
                    $attributes[$att] = preg_replace_callback(kleeja_style::reg('var'), array('kleeja_style', '_var_callback'), $attribute[2][$i]);
                }
				else
				{
                    $attributes[$att] = preg_replace_callback(kleeja_style::reg('var'), array('kleeja_style', '_var_callback_att'), $attribute[2][$i]);
                }
            }
            return $attributes;
        }

        //Assign Veriables
        function assign($var, $to)
		{
            $GLOBALS[$var] = $to;
        }

        //load parser and return page content
        function display($template_name)
		{
			global $config, $SQL;

			$this->vars 	= &$GLOBALS;
			$k 				= '<div sty' . 'le="di' . 'spl'. 'ay:bl'. 'oc' . 'k !im' . 'po' . 'rt' . 'ant;' . 'backgrou' . 'nd:#ECE' .'CE' . 'C !im' . 'po' . 'rt' . 
								'ant;margin:5p' . 'x; padding:2px 3px; position:fi' . 'xed;bottom' . ':0px;left:1%' . ';z-index:9' . '9999;text' . '-align:center;">P' . 
								'owe' . 'red b' . 'y <a style="di' . 'spl'. 'ay:in'. 'li' . 'ne  !im' . 'po' . 'rt' . 'ant;' . 'color:#6' . 
								'2B4E8 !im' . 'po' . 'rt' . 'ant;" href="http:' . '/' . '/ww' . 'w.kl' . 'ee' . 'ja.c' . 'om/" onclic' . 'k="windo' . 'w.op' . 'en(this.h' . 
								'ref,' . '\'_b' . 'lank\');retur' . 'n false;" title' . '="K' . 'lee' . 'ja">K' . 'lee' . 'ja</a></div>' . "\n";
			//is there ?
			if(!file_exists(PATH . 'cache/tpl_' . $this->re_name_tpl($template_name) . '.php') or !$this->caching)
			{
				$this->_load_template($template_name);
			}

			ob_start();
			include(PATH . 'cache/tpl_' . $this->re_name_tpl($template_name) . '.php');
			$page = ob_get_contents();
			ob_end_clean();

			if($template_name == strip_tags('<!--it-->he<!--Is-->ad<!--Queen-->er'))
			{
				$v = @unserialize($config['new_version']);
				if((int) $v[strip_tags('co<!--it-->py<!--made-->ri<!--for-->gh<!--you-->ts<!--yub-->')] == /*kleeja is sweety*/0/*SO, be sweety*/)
				{
					$t = strip_tags('<!--y-->b<!--o-->o<!--n-->d<!--b-->y');
					$page = preg_replace('/<' . $t . '[^>]*>/', '<' . $t . ">\n" . $k, $page, -1, $c);
					if(!$c)
					{
						$page .= $k;
					}
				}
			}

			return $page;
		}

		function admindisplayoption($html)
		{
			global $config, $SQL;
			
			$this->vars	= &$GLOBALS;
			$this->HTML	= $html;
			$this->_parse($this->HTML);

 			ob_start();
			eval(' ?' . '>' . trim($this->HTML) . '<' . '?php ');
			$page = ob_get_contents();
			ob_end_clean();
		
			return $page;
		}
		
		//change name of template to be valid 1rc6+
		function re_name_tpl($name)
		{
			return preg_replace("/[^a-z0-9-_]/", "-", strtolower($name));
		}
		
		
		//
		function kleeja_style()
		{
			
		}
}
