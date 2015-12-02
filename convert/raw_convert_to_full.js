
var fs = require("fs");
var csv = require("fast-csv");

var csvStream2 = csv.createWriteStream({headers: true});
var writableStream = fs.createWriteStream("data/daily/info_20151128.csv");
csvStream2.pipe(writableStream);


var CNOs = JSON.parse(fs.readFileSync("data/工廠清單.json"));
var CODEs = JSON.parse(fs.readFileSync("data/項目代碼.json"));

var CNOMap = CNOs.reduce(function(now,next){
  now[next["管制編號"]] = next;
  return now;
},{});

var codeMap = CODEs.reduce(function(now,next){
  now[next.ITEM] = next;
  return now;
},{});


var items = [];

var parseTime = function(time){
  time = parseInt(time,10);
  return new Date("2015/11/28 "+ parseInt(time/100,10)+":"+(time%100) );
}


var stream = fs.createReadStream("data/daily/raw_20151128.csv");


var csvStream = csv({headers:true})
    .on("data", function(data){
      data.TIME = parseTime(data.M_TIME);
      data.timestamp = parseTime(data.M_TIME).getTime();

      if(CNOMap[data.CNO] != null){
        var cno = CNOMap[data.CNO];
        data.CNOName = cno["工廠"];
        data.CNOLat = cno["Lat"];
        data.CNOLng = cno["Lng"];
      }else{
        data.CNOName = null;
        data.CNOLat = null;
        data.CNOLng = null;
      }

      if(codeMap[data.ITEM] != null){
        var code = codeMap[data.ITEM];
        data.ItemName = code.DESP;
        data.ItemABBR = code.ABBR;
        data.ItemUNIT = code.UNIT;
      }else{
        data.ItemName = null;
        data.ItemABBR = null;
        data.ItemUNIT = null; 
      }
      csvStream2.write(data);
      items.push(data);
    })
    .on("end", function(){
      csvStream2.end();
      console.log("done");

       
      fs.writeFileSync("data/daily/info_20151128.json",
        JSON.stringify(items.sort(function(o1,o2){ return o1.TIME.getTime() - o2.TIME.getTime(); }))
      );


    });

stream.pipe(csvStream);


