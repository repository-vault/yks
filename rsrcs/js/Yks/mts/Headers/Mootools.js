
Doms.loaders['[MT]/Drag/Drag'] = {
    "class": "Drag",
    "match": false,
    "descr": "Drag"
};

Doms.loaders['[MT]/Drag/Drag.Move'] = {
    "class": "Drag.Move",
    "match": false,
    "descr": "Drag.move",
    "deps" : ["[MT]/Drag/Drag"],
    "patch": ["[PATCH]/Drag/Drag.Move"]
};

Doms.loaders['[MT]/Interface/Accordion'] = {
    "class": "Accordion",
    "match": false,
    "patch": ["[PATCH]/Interface/Accordion"]
};


Doms.loaders['[MT]/Utilities/Cookie'] = {
    "class": "Cookie",
    "match": false,
    "descr": "Cookie utilities",
    "patch": ["[PATCH]/Utilities/Cookie"]
};

Doms.loaders['[MT]/Interface/Slider'] = {
    "class": "Slider",
    "match": false,
    "descr": "Slider"
};

Doms.loaders['[MT]/Fx/Fx.Scroll'] = {
    "class": "Fx.Scroll",
    "match": false,
    "descr": "Effect to smoothly scroll any element, including the window."
};
