
Doms.loaders['path://yks/Jsx/Forms.js'] = {
    'class': Jsx.Form,
    'match': 'form.jsx_form',
    'descr': "Extend and submit all forms via AJAX, designed to be used in a Ex/yks layer"
};

Doms.loaders['path://yks/Jsx/Box.js'] = {
    'class': Box,
    'match': Doms.box_mask,
    'descr': "Root of the Ex/Yks JS layer, defined a virtual container, act as an 'iframe' "
};

Doms.loaders['path://yks/Jsx/Uploader.js'] = {
    'class': 'Uploader',
    'match': false,
    'descr': "Deal with uploaded stuffs"
};

Doms.loaders['path://yks/Jsx/Links.js'] = {
    'class': Jsx.A,
    'match': "*[href]:not(.ext):not(.std):not([href^='#']):not([target='_top']):not([href^='mailto:'])",
    'descr': "Extend basic links and add support for [target] attribute, working with Boxes"
};


Doms.loaders['path://yks/Utilities/DatePicker.js'] = {
    'class': 'DatePicker',
    'match': 'input.input_time',
    'descr': "Extend a basic text input into a small but usefull calendar"
};

Doms.loaders['path://yks/Utilities/Rte/Wyzzie.js'] = {
    'class': 'Wyzzie',
    'match': 'textarea.wyzzie',
    'descr': "Provide a very light Rich Text Editor for editing html contents"
};

Doms.loaders['path://yks/Utilities/Completer.js'] = {
    'class': 'Completer',
    'match': false,
    'descr': "Autocomplete"
};

