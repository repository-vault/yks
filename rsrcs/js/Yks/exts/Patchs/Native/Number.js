Number.implement({
  between:function(min,max){ return this>=min && this<=max; },
  lt:function(max){ return this<max; },
  gt:function(max){ return this>max; }
});

Math.sign = function(a){ return a<0?-1:1; }