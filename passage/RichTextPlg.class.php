<?php


/**
 * Клас 'doc_RichTextPlg' - Добавя функционалност за поставяне handle на документи в type_Richtext
 *
 *
 * @category  bgerp
 * @package   doc
 * @author    Yusein Yuseinov <yyuseinov@gmail.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class passage_RichTextPlg extends core_Plugin
{

    /**
     * Добавя бутон за качване на документ
     *
     * @param core_Mvc $mvc
     * @param core_ObjectCollection $toolbarArr
     * @param array $attr
     */
    public static function on_AfterGetToolbar($mvc, &$toolbarArr, &$attr)
    {
        // Ако има права за добавяне
        if (haveRole('powerUser')) {

            // id
            $id = $attr['id'];

            // Име на функцията и на прозореца
            $windowName = $callbackName = 'placePassage_' . $id;

            // Ако е мобилен/тесем режим
            if(Mode::is('screenMode', 'narrow')) {

                // Парамтери към отварянето на прозореца
                $args = 'resizable=yes,scrollbars=yes,status=no,location=no,menubar=no,location=no';
            } else {
                $args = 'width=600,height=600,resizable=yes,scrollbars=yes,status=no,location=no,menubar=no,location=no';
            }

            // URL за добавяне на документи
            $url = toUrl(array('passage_Texts', 'Dialog'));

            // JS фунцкията, която отваря прозореца
            $js = "openWindow('{$url}', '{$windowName}', '{$args}'); return false;";

            // Бутон за отвяряне на прозореца
            $addPassage = new ET("<a class=rtbutton title='" . tr("Добавяне на пасаж ") . "' onclick=\"{$js}\">" . tr("Пасаж") . "</a>");


            // JS функцията
            $callback = "function {$callbackName}(passage) {
                var ta = get$('{$id}');
                rp(passage, ta, 1);
                return true;
            }";

            // Добавяме скрипта
            $addPassage ->appendOnce($callback, 'SCRIPTS');

            // Добавяне в групата за добавяне на документ
            $toolbarArr->add($addPassage , 'filesAndDoc', 1000.055);
        }
    }


    /**
     * Прихваща никовете и създава линкове към сингъла на профилите
     *
     * @param array $match
     */
    function _catchNick($match)
    {
        // Да не сработва в текстов режим
        if (Mode::is('text', 'plain') || Mode::is('text', 'xhtml')) return $match[0];

        // Вземаме id на записа от ника
        $nick = $match['nick'];
        $nick = strtolower($nick);
        $id = core_Users::fetchField(array("LOWER (#nick) = '[#1#]'", $nick));

        if (!$id) return $match[0];

        // Добавяме в борда
        $place = $this->mvc->getPlace();

        // За ника използваме и префикса от стринга
        $nick = $match['pre'] . type_Nick::normalize($match['nick']);

        $profileId = crm_Profiles::getProfileId($id);

        $this->mvc->_htmlBoard[$place] = crm_Profiles::createLink($id, $nick);

        return "[#{$place}#]";
    }
}
