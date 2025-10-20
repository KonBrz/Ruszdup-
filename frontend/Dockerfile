FROM node:20

WORKDIR /app

# Copy only package files first (so Docker caches npm install)
COPY package*.json ./

# Install dependencies inside container
RUN npm install

# Then copy the rest of your app code
COPY . .

EXPOSE 5173

CMD ["npm", "run", "dev", "--", "--host"]