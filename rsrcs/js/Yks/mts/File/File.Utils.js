

var FileUtils = {
  format_size:function(size){
    return (size>>30)?(size/(1<<30)).round(2)+' Go':
        ((size>>20)?(size/(1<<20)).round(2)+' Mo':
            ((size>>10)?(size/(1<<10)).round(2)+' Ko':size+" octets"));
  }
};
