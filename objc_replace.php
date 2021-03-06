
<?php
  
  function condestructors_check($method_name, $check, $is_header)
  {
    // handle methods name which includes "init" but not constructor
    $tmp_check = $check;
    
    //handle constructors & destructors
    $found_constructors_line = strstr($tmp_check, "init()");
    if ($found_constructors_line !== FALSE)
    {
      if ($is_header === 'false')
        $check = $method_name."::".$method_name."()";
      else
        $check = $method_name."()";
    }
    
    $found_destructors_line = strstr($tmp_check, "dealloc()");
    if ($found_destructors_line !== FALSE)
    {
      if ($is_header === 'false')
        $check = $method_name."::~".$method_name."()";
      else
        $check = "virtual ~".$method_name."()";
    }
    
    return $check;
  }
  
  function have_param_check($check)
  {
    // remove all "()" in obj-c method
    $check = str_replace("(", "", $check);
    $check = str_replace(")", " ", $check);
    
    //handle constructors & destructors
    $found_param_line = strstr($check, ":");
    if ($found_param_line !== FALSE)
    {
      $check = substr_replace($check, "(", strpos($check, ":") - strlen($check), strpos($check, ":") - strlen($check) + 1).")";
    } else
      $check = $check."()";
    
    return $check;
  }
  
  function replace_method_keyword($search, $replace, $subject, $method_name, $is_header)
  {
    $found_method_line = strstr($subject, $search);
    if ($found_method_line !== FALSE)
    {
      
      $found_method_line = str_replace($search, $replace, $found_method_line);
      $found_method_line = substr_replace($found_method_line, "", strpos($found_method_line, ")") - strlen($found_method_line), 1);
      
      $found_method_line = have_param_check($found_method_line);
      
      if ($is_header === 'false')
      {
        $found_method_line = substr_replace($found_method_line, " ".$method_name."::", strpos($found_method_line, " ") - strlen($found_method_line), strpos($found_method_line, " ") - strlen($found_method_line) + 1);
      }
      
      $found_method_line = condestructors_check($method_name, $found_method_line, $is_header);
      
    }
    
    return $found_method_line;
  }
  
  
  $objc_char = array('@',
                     'CGFloat',
                     'NSUInteger',
                     'CG',
                     'kCTCenterTextAlignment',
                     'kCTLeftTextAlignment',
                     'kCTRightTextAlignment',
                     '[PIScene class]',
                     '[PIWorld _instance]',
                     '[CCDirector sharedDirector]',
                     'CCString *',
                     'CCString stringWithFormat:',
                     'UITouch',
                     ' withEvent:UIEvent',
                     ' setTexture:',
                     'setTextureRect',
                     ' valueForKey:',
                     ' addChild:',
                     ' setFrame:',
                     ' addText:',
                     ' setText:',
                     ' setFontColor:',
                     ' _instance] ',
                     ' getFloatConfig:',
                     'get:',
                     ' index:',
                     ' font_color:',
                     ' fontSize:',
                     ' fontName:',
                     'CCLabelTTF labelWithString:',
                     'getSceneByKind:PIScene class]]',
                     'CCString stringWithFormat:',
                     'NSDate *',
                     'NSDate dateWithTimeIntervalSince1970:',
                     ' nor:',
                     ' res:',
                     ' closeWithImpClass:',
                     ' node]',
                     'self.',
                     '[self ',
                     'self',
                     'BOOL',
                     'nil',
                     'YES',
                     //'NO',
                     '[',
                     ']',
                     );
  
  $c_char = array('',
                  'float',
                  'unsigned int',
                  'CC',
                  'CCTextAlignmentCenter',
                  'CCTextAlignmentLeft',
                  'CCTextAlignmentRight',
                  '(PISceneCurrent)',
                  'PIWorld::_instance()',
                  'CCDirector::sharedDirector()',
                  'string ',
                  'PIRegex::PIStringFormat(',
                  'CCTouch',
                  ', CCEvent',
                  '->setDisplayFrame(',
                  '此行无效',
                  '->objectForKey(',
                  '->addChild(',
                  '->setFrame(',
                  '->addText(',
                  '->setText(',
                  '->setFontColor(',
                  '::_instance()->',
                  '->getFloatConfig(',
                  '->get(',
                  ', ',
                  ', ',
                  ', ',
                  ', ',
                  'CCLabelTTF::labelWithString(',
                  'getSceneByKind(PISceneCurrent)',
                  'PIRegex::PIStringFormat(',
                  'string ',
                  'PICommonFunctions::formatServerWarTime(',
                  ', ',
                  ', ',
                  '->closeWithUIType(',
                  '::object()',
                  'this->',
                  'this->',
                  'this',
                  'bool',
                  'NULL',
                  'true',
                  //'false',
                  '',
                  ')',
                  );
  
  $list = array($objc_char, $c_char);
  
  $filename = $_SERVER['argv'][1];
  
  if ($filename === NULL || $filename === '')
  {
    echo "Usage: php -f objc_replace.php [filename]\n";
    exit;
  }
  
  $content = file_get_contents($filename);
  
  if ($content === FALSE)
  {
    echo "\nOpen file ".$filename." is NULL!\n";
    exit;
  }
  
//  foreach ($list as $line)
//  {
//    $before = $line[0];
//    $after = $line[1];
//    echo "Bebore: ".$before."\nAfter: ".$after."\n";
//  }
  
  //echo $content;
  

  
  // find all methods & generate header file
  $arr = explode("\n", $content);
  $methods_solved = "";
  $method_name = "";
  
  foreach ($arr as $line)
  {
    $tmp_method_name = strstr($line, "@implementation");
    if ($tmp_method_name !== FALSE)
    {
      $method_name = str_replace("@implementation ", "", $tmp_method_name);
      $methods_solved = $methods_solved."\nclass ".$method_name."\n{";
      continue;
    }
    
    $class_end_sign = strstr($line, "@end");
    if ($class_end_sign !== FALSE)
    {
      $methods_solved = $methods_solved."\n};\n";
      continue;
    }
    
    $handle_result = replace_method_keyword("-(", "", $line, $method_name, 'true');
    if ($handle_result !== FALSE)
    {
      $methods_solved = $methods_solved."\n  ".$handle_result.";";
    }
    
    $handle_result = replace_method_keyword("+(", "static ", $line, $method_name, 'true');
    if ($handle_result !== FALSE)
    {
      $methods_solved = $methods_solved."\n  ".$handle_result.";";
    }
    
  }
  
  $arr = explode("\n", $content);
  $content_solved = "";
  foreach ($arr as $line)
  {
    $tmp_method_name = strstr($line, "@implementation");
    if ($tmp_method_name !== FALSE)
    {
      $method_name = str_replace("@implementation ", "", $tmp_method_name);
      continue;
    }
    
    $handle_result = replace_method_keyword("-(", "", $line, $method_name, 'false');
    if ($handle_result !== FALSE)
    {
      $content_solved = $content_solved."\n".$handle_result;

      continue;
    }
    
    $handle_result = replace_method_keyword("+(", "", $line, $method_name, 'false'); // we don't need to add 'static' in implementation
    if ($handle_result !== FALSE)
    {
      $content_solved = $content_solved."\n".$handle_result;
      
      continue;
    }
    
    $class_end_sign = strstr("@".$line, "@end");
    if ($class_end_sign !== FALSE)
    {
      continue;
    }
    
    $content_solved = $content_solved."\n".$line;
  }
    
  $content_solved = str_replace($objc_char, $c_char, $content_solved);
  $methods_solved = str_replace($objc_char, $c_char, $methods_solved);
  
  file_put_contents($filename.".solved", $content_solved);
  file_put_contents($filename.".header", $methods_solved);
  
  echo "\nFile saved to ".$filename.".solved\n";
  echo "\nFile saved to ".$filename.".header\n";
  
?>
