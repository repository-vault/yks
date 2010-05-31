var DatePicker = new Class({
  Extends: DatePicker,
  initialize:function(element, options){

    var tmp = {
        pickerClass: 'datepicker_vista',
        format:'d/m/Y',
        allowEmpty:true

    };

    if(element.hasClass('input_time')) {
        tmp.format = 'd/m/Y H:i';
        tmp.timePicker = true;
    }

    tmp.inputOutputFormat = tmp.format ;

    if(! element.get('value').match(/^[ 0-9/:]*$/))
        element.set('value', '');

    this.parent(element, tmp);

  }
});
