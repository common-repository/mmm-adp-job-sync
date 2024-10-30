<?php namespace adpjsformtools;
class FormHelpers
{
    public static function gen_field($label, $name, $value, $type="text")
    {
        switch ($type) {
            case 'editor':
                return FormHelpers::gen_editor($label, $name, $value);
                break;
            case 'textarea':
                return FormHelpers::gen_textarea($label, $name, $value);
                break;
            case 'multiinput':
                return FormHelpers::gen_multi_input($label, $name, $value);
                break;
            case 'multifaq':
                return FormHelpers::gen_multi_faq($label, $name, $value);
                break;
            case 'color':
                return FormHelpers::gen_color_input($label, $name, $value);
                break;
            case 'toggle':
                return FormHelpers::gen_toggle($label, $name, $value);
                break;
            case 'select':
                return FormHelpers::gen_select($label, $name, $value);
                break;
            case 'text':
            default:
                return FormHelpers::gen_input($label, $name, $value);
                break;
        }
    }
    public static function _gen_label($label)
    {
        return sprintf('<label>%s</label>', $label);
    }
    public static function gen_select($label, $name, $value)
    {
        $wrapper = '<div class="vmp-item">' . FormHelpers::_gen_label($label). '<select name="%s">%s</select></div>';
        $output = "";

        if (is_array($value))
        {
            $selected = $value["selected"];
            $options = $value["options"];

            foreach ($options as $option) {
                $output .= FormHelpers::gen_option($option["value"], $option["label"], $option["value"] == $selected);
            }
        }

        return sprintf($wrapper, $name, $output);
    }
    public static function gen_option($value, $label, $selected=false)
    {
        $selectedAttr = ($selected)?'selected="selected"':'';
        return sprintf('<option value="%s"%s>%s</option>', $value, $selectedAttr, $label);
    }
    public static function gen_multi_input($label, $name, $value)
    {
        $wrapper = '<div id="vmp_input_item_list_%s">%s</div>';
        $additem_action = '<a style="display:none;" href="javascript:void(0);">Add</a>';
        $template = '<template id="vmp_template_%s">' .
                    FormHelpers::gen_input($label, $name . "{ID}", "") .
                    '</template>';
        $output = "";
        if (is_array($value))
        {
            for ($i=0; $i<count($value);$i++) {
                $output .= FormHelpers::gen_input($label . " #" . $i, $name . $i, $value[$i]);
            }
        }
        return sprintf($wrapper, $name, $output) . sprintf($template, "vmp_input_template_" . $name, $label, $name, "");
    }
    public static function gen_multi_faq($label, $name, $value)
    {
        $wrapper = '<div id="vmp_faq_item_list_%s">%s</div>';
        $additem_action = '<a style="display:none;" href="javascript:void(0);">Add</a>';
        $template = '<template id="vmp_faq_template_%s">' .
                    FormHelpers::gen_faq($label, $name . "{ID}", "{ID}", "", "", "") .
                    '</p></div></template>';
        $output = "";
        if (is_array($value))
        {
            for ($i=0; $i<count($value);$i++) {
                $qa = $value[$i];
                $output .= FormHelpers::gen_faq($label . " #" . $i . "<br />", $i, $name, $qa->question, $qa->answer, $value[$i]);
            }
        }
        return sprintf($wrapper, $name, $output) . sprintf($template, "vmp_faq_template_" . $name, $label, $name, "");
    }
    public static function gen_faq($label, $name, $id, $question, $answer, $json)
    {
        $template = '<div class="vmp-item">' .
                    FormHelpers::_gen_label($label) . 
                    'Queston <input type="text" name="%1$s_q_%2$s" value="%3$s">' .
                    'Answer <textarea type="text" name="%1$s_a_%2$s">%4$s</textarea>' .
                    '<input type="hidden" name="%1$s%2$s" value="">' .
                    '</div>';
        return sprintf($template, $name, $id, $question, $answer);
    }
    public static function gen_input($label, $name, $value)
    {
        $template = '<div class="vmp-item">'.FormHelpers::_gen_label($label).'<input type="text" name="%s" value="%s"></div>';
        return sprintf($template, $name, esc_textarea($value));
    }
    public static function gen_color_input($label, $name, $value)
    {
        $template = '<div class="vmp-item">'.FormHelpers::_gen_label($label).'<input type="color" name="%s" value="%s"></div>';
        return sprintf($template, $name, esc_textarea($value));
    }
    public static function gen_toggle($label, $name, $value)
    {
        $template = '<div class="vmp-item">'.FormHelpers::_gen_label($label).'<input type="checkbox" name="%s" value="%s"%s></div>';
        $checked = ($value==1)?' checked="checked"':"";
        return sprintf($template, $name, $value, $checked);
    }
    public static function gen_textarea($label, $name, $value)
    {
        $template = '<div class="vmp-item">'.FormHelpers::_gen_label($label).'<textarea type="text" name="%s">%s</textarea></div>';
        return sprintf($template, $name, esc_textarea($value));
    }
    public static function gen_editor($label, $name, $value)
    {
        $template = '<div class="vmp-item">'.FormHelpers::_gen_label($label).'%s</div>';
        ob_start( );
        wp_editor ( 
           htmlspecialchars_decode( $value ), 
           $name,
           array ( "media_buttons" => true ) 
          );
        $editor = ob_get_clean( );
        return sprintf($template, $editor);
    }
}