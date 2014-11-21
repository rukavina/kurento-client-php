
var WebSocketServer = require('websocket').server;
var http = require('http');
var connections = {};
var connectionDescs = {};

var server = http.createServer(function(request, response) {
    // process HTTP request. Since we're writing just WebSockets server
    // we don't have to implement anything.
    });
server.listen(8080, function() {
    console.log((new Date()) + " Server is listening on port 8080");
});

// create the server
wsServer = new WebSocketServer({
    httpServer: server
});


wsServer.on('request', function(request) {
    console.log((new Date()) + ' Connection from origin ' + request.origin + '.');
    var connection = request.accept(null, request.origin);
    console.log(' Connection ' + connection.remoteAddress);
    
    // This is the most important callback for us, we'll handle
    // all messages from users here.
    connection.on('message', function(message) {
        console.log("Received Message: ");
        console.log(message);
        connection.sendUTF(message.utf8Data);
    });        
});