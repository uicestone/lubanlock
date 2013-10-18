var superCoolLib = {
    saySomethingAndDoCallback : function(word,cb){
          console.log(word);
              cb.apply("I am this");
                }
}


superCoolLib.saySomethingAndDoCallback("word",function(){
    console.log(this);
});
