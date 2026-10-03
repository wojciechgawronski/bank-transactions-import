FROM node:22-bookworm-slim

# npm 10 bundled with Node 22 crashes on this dependency tree (edgesOut bug)
RUN npm install -g npm@11

WORKDIR /app
RUN chown node:node /app
USER node

COPY --chown=node:node package.json package-lock.json ./
RUN npm ci

COPY --chown=node:node . .

EXPOSE 5173

CMD ["npm", "run", "dev", "--", "--host", "0.0.0.0"]
