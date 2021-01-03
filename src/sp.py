#coding=utf-8
import requests
import json



def getIp(proxyHost,proxyPort):
    #请求地址
    targetUrl = "https://api.ipify.org/?format=jsonp&callback="
    proxyMeta = "http://%(host)s:%(port)s" % {"host" : proxyHost,"port" : proxyPort}
    proxies = {"http":proxyMeta,"https":proxyMeta}
    print(proxies)
    try:
        resp = requests.get(targetUrl, proxies=proxies)
        print(resp.status_code)
        print(resp.text)
    except Exception as e:
        print('error')

with open('./iplog/ip.json','r')  as fileObj:
    info = json.load(fileObj)
    for key,val in info.items():
        getIp(val['ip'],val['port'])








#pip install -U requests[socks]  socks5
# proxyMeta = "socks5://%(host)s:%(port)s" % {

#     "host" : proxyHost,

#     "port" : proxyPort,

# }

