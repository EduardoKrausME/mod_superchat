# mod_superchat

## Instalando o Módulo no Moodle

Navegue até a pasta mod do seu Moodle e execute o seguinte comando:

```
# git clone https://github.com/EduardoKrausME/mod_superchat.git superchat 
```

Ou baixe em http://github.com/EduardoKrausME/mod_superchat/archive/master.zip

> Se baixar o ZIP, não se esqueça de renomear a pasta de **mod_superchat-master** para **superchat**.

Para instalar diretamente pelo painel do Moodle, use este link aqui: https://github.com/EduardoKrausME/mod_superchat/blob/master/superchat.zip

Após, acesse a página de avisos do seu Moodle e instale normalmente o plug-in do Super Chat.

Ele pedirá dois dados:
* **Servidor:** É a maquina ao qual esta instalado o rodando o Node.js. Por padrão o plug-in colocará seu próprio servidor. 
  * *Lembre-se que não vai HTTP:// só o domínio ou o IP*
  * **Padrão:** Seu domínio
* **Porta:** porta que esta rodando o Node.js. 
  * *Não se esqueça de liberar no firewall*
  * **Padrão:** 8080 

## Instalando o Node.js

No seu servidor, execute a seguinte sequência de comandos para instalar o Node.js

```
# cd /usr/src/
# wget http://nodejs.org/dist/node-latest.tar.gz
# tar -zxvf node-latest.tar.gz 
# cd node-v*
# sudo sudo ./configure
# sudo make
# sudo make install
# sudo ln -s /usr/local/bin/npm  /usr/bin/npm
# sudo ln -s /usr/local/bin/node /usr/bin/node
```

## Finalizando...

Após instalado, é só navegar até a pasta que você instalou o módulo do Super Chat em [MOODLE_INSTALL]/mod/superchat/_node e execute o seguinte comando para instalar as dependências:


```
# npm install
```

Após as dependências instaladas, rode o servidor com o seguinte comando:

```
# node app.js 
```

Pronto. É só usar a vontade.
