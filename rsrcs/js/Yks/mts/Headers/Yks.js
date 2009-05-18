
Doms.loaders['[YKS]/Jsx/Forms'] = {
    'class': Jsx.Form,
    'match': 'form.jsx_form',
    'descr': "Extend and submit all forms via AJAX, designed to be used in a Ex/yks layer"
};

Doms.loaders['[YKS]/Jsx/Box'] = {
    'class': Box,
    'match': Doms.box_mask,
    'descr': "Root of the Ex/Yks JS layer, defined a virtual container, act as an 'iframe' "
};

Doms.loaders['[YKS]/Jsx/Uploader'] = {
    'class': 'Uploader',
    'match': false,
    'descr': "Deal with uploaded stuffs"
};

Doms.loaders['[YKS]/Jsx/Links'] = {
    'class': Jsx.A,
    'match': "*[href]:not(.ext):not(.std):not([href^='#']):not([target='_top']):not([href^='mailto:'])",
    'descr': "Extend basic links and add support for [target] attribute, working with Boxes"
};


Doms.loaders['[YKS]/Interface/Toggler'] = {
    'class': 'Toggler',
    'match': 'div.toggle_zone',
    'descr': "Toggler is a very light Accordion like"
};

Doms.loaders['[YKS]/Utilities/DatePicker'] = {
    'class': 'DatePicker',
    'match': 'input.input_time',
    'descr': "Extend a basic text input into a small but usefull calendar"
};

Doms.loaders['[YKS]/Utilities/Rte/Wyzzie'] = {
    'class': 'Wyzzie',
    'match': 'textarea.wyzzie',
    'descr': "Provide a very light Rich Text Editor for editing html contents"
};

Doms.loaders['[YKS]/Utilities/Completer'] = {
    'class': 'Completer',
    'match': false,
    'descr': "Autocomplete"
};

