/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


//Plugin
(function ($) {
    $.fn.smsArea = function (options) {
        
        var
            e = this,
            smsCount = $(e).attr('id') + '-smsCount',
            smsLength = $(e).attr('id') + '-smsLength',
            cutStrLength = 0,
            s = $.extend({
                cut: true,
                maxSmsNum: 3,
                interval: 400,
                counters: {
                    message: $('.' + smsCount),
                    character: $('.' + smsLength)
                },
                lengths: {
                    ascii: [160, 306, 459],
                    unicode: [70, 134, 201]
                }
            }, options);

        e.keyup(function () {
            
            clearTimeout(this.timeout);
            this.timeout = setTimeout(function () {

                var
                        smsType,
                        smsLength = 0,
                        smsCount = -1,
                        charsLeft = 0,
                        text = e.val(),
                        isUnicode = false;

                for (var charPos = 0; charPos < text.length; charPos++) {
                    switch (text[charPos]) {
                        case "\n":
                        case "[":
                        case "]":
                        case "\\":
                        case "^":
                        case "{":
                        case "}":
                        case "|":
                        case "€":
                            smsLength += 2;
                            break;

                        default:
                            smsLength += 1;
                    }

                    //!isUnicode && text.charCodeAt(charPos) > 127 && text[charPos] != "€" && (isUnicode = true)
                    if (text.charCodeAt(charPos) > 127 && text[charPos] !== "€")
                        isUnicode = true;
                }

                if (isUnicode)
                    smsType = s.lengths.unicode;
                else
                    smsType = s.lengths.ascii;

                for (var sCount = 0; sCount < s.maxSmsNum; sCount++) {

                    cutStrLength = smsType[sCount];
                    if (smsLength <= smsType[sCount]) {

                        smsCount = sCount + 1;
                        charsLeft = smsType[sCount] - smsLength;
                        break
                    }
                }

                if (s.cut)
                    e.val(text.substring(0, cutStrLength));
                smsCount === -1 && (smsCount = s.maxSmsNum, charsLeft = 0);

                s.counters.message.html(smsCount);
                s.counters.character.html(charsLeft);

            }, s.interval);
        }).keyup();
    };
}(jQuery));
