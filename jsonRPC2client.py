"""
					COPYRIGHT

Copyright 2012 Stijn Van Campenhout <stijn.vancampenhout@gmail.com>

This file is part of JSON-RPC2PHP.

JSON-RPC2PHP is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

JSON-RPC2PHP is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with JSON-RPC2PHP; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
"""
__name__    = "jsonrcp2client"
__author__  = "Stijn Van Campenhout <stijn.vancampenhout@gmail.com>"
__version__ = 1,2
__detail__  = "For use with http://github.com/subutux/json-rpc2php/"

import json
import urllib
import urllib2
class jsonrpc2client(object):
	"""Jsonrcp2php client for python"""
	host = ""
	defaultOptions = {
	"ignoreErrors" : [],
	"username" : "",
	"password" : "",
	"sessionId" : ""
	}
	currId = 0
	useClass = ""
	apiMethods = []
	def __init__(self,apiUrl,useClass,options=None):
		self.host = apiUrl
		self.useClass = useClass
		if options is not None:
			for i in options:
				self.defaultOptions[i] = options[i]
		returned = self.rpcCall('rpc.listMethods')
		self.apiMethods = returned["result"][useClass]
	def rpcCall(self,method,params=None,notification=False):
		"""main function to call the rpc api"""
		if notification is False:
			self.currId = self.currId + 1
			request = {
			"jsonrpc" : "2.0",
			"method" : method,
			"params" : [],
			"id" : self.currId
			}
		else:
			request = {
			"jsonrpc" : "2.0",
			"method" : method,
			"params" : []
			}

		if isinstance(params,str):
			request["params"] = [params]
		elif isinstance(params,list):
			request["params"] = params
		else:
			request["params"] = ''
		jsonrequest = json.dumps(request)
		return self.__send(jsonrequest,notification)

	def rpcBatchCall(self,arrayOfRequests):
		requests = [];
		for request in arrayOfRequests:
			RPCcall = {
				"jsonrpc" : "2.0",
				"method" :self.useClass + "." + request["method"],
				}
			if request.has_key("notification"):
				if request["notification"] is True:
					RPCcall["id"] = self.currId + 1
			else:
				RPCcall["id"] = self.currId + 1
			if request.has_key("params"):
				if isinstance(request["params"],str):
					RPCcall["params"] = [request["params"]]
				elif isinstance(request["params"],list):
					RPCcall["params"] = request["params"]
				else:
					RPCcall["params"] = ''
			else:
				RPCcall["params"] = ''
			requests.append(RPCcall)
		jsonrequest = json.dumps(requests)
		return self.__send(jsonrequest,False)

	def __send(self,jsonrequest,notification,):
		headers = {"Content-Type": "application/json","Content-lenght":str(len(jsonrequest))}
		if self.defaultOptions["username"] is not "" and self.defaultOptions["password"] is not "":
			if self.defaultOptions["sessionId"] is "":
					headers['x-RPC-Auth-Username'] = self.defaultOptions["username"]
					headers['x-RPC-Auth-Password'] = self.defaultOptions["password"]
			else:
				headers['x-RPC-Auth-Session'] = self.defaultOptions['sessionId']
		req = urllib2.Request(self.host,headers = headers, data = jsonrequest)
		fr = urllib2.urlopen(req)
		sessionId = fr.info().getheader('x-RPC-Auth-Session')
		if type(sessionId) is str:
			self.defaultOptions["sessionId"] = sessionId
		f = fr.read()
		if f is not "":
			f_obj = json.loads(f)
			if (isinstance(f_obj,dict)):
				f_obj = [f_obj]
			for f_object in f_obj:
				#TODO: Make this properly. Re-write exception to accept lists of errors
				if f_object["error"] is not None:
					raise rpcException(f_object["error"])
			if len(f_obj) == 1:
				return f_obj[0]
			else:
				return f_obj
	def __getattr__(self,method):
		"""Magic!"""
		arg = ['',False]
		if method in self.apiMethods:
			def function(*args):
				# Get the method arguments. If there are none provided, use the default.
				try:
					arg[0] = args[0]
				except IndexError: pass
				# check if notification param is set. If not, use default (False)
				try:
					arg[1] = args[1]
				except IndexError: pass

				return self.rpcCall(self.useClass + "." + method,arg[0],arg[1])
			return function
		else:
			raise rpcException("Method unknown in class \"" + self.useClass + "\"")

class rpcException(Exception):
	def __init__(self,jsonrpc2Error):
		if type(jsonrpc2Error) is not str:
			print jsonrpc2Error
			message = str(jsonrpc2Error["code"]) + "::" + jsonrpc2Error["message"]
			self.errorCode = jsonrpc2Error["code"]
			self.message = jsonrpc2Error["message"]
			self.fullMessage = jsonrpc2Error['data']["fullMessage"]
		else:
			message = jsonrpc2Error
		Exception.__init__(self, message)
rpc = jsonrpc2client("http://localhost/json-rpc2php/api.php","myClass",{
	"username": "test",
	"password" : "test"
	})
print("Simple:")
print(rpc.ping("simple"))
print("Batch:")
print(rpc.rpcBatchCall([
	{
	"method" : "ping",
	"params"	: "test"
	},{
	"method" : "ping",
	"params"	: "test2"
	},{
	"method" : "ping",
	"params"	: "test3"
	}]))
