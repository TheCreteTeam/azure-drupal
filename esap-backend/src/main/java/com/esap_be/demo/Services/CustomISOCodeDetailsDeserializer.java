package com.esap_be.demo.Services;

import com.fasterxml.jackson.core.JsonParser;
import com.fasterxml.jackson.core.JsonProcessingException;
import com.fasterxml.jackson.core.type.TypeReference;
import com.fasterxml.jackson.databind.DeserializationContext;
import com.fasterxml.jackson.databind.JsonDeserializer;
import com.fasterxml.jackson.databind.JsonNode;
import com.esap_be.demo.Models.Entities.ISOCodeDetails;
import com.fasterxml.jackson.databind.ObjectMapper;

import java.io.IOException;
import java.util.ArrayList;
import java.util.HashMap;
import java.util.List;
import java.util.Map;

public class CustomISOCodeDetailsDeserializer extends JsonDeserializer<Map<String, List<ISOCodeDetails>>> {

    @Override
    public Map<String, List<ISOCodeDetails>> deserialize(JsonParser p, DeserializationContext context) throws IOException {
        JsonNode node = p.getCodec().readTree(p);
        Map<String, List<ISOCodeDetails>> result = new HashMap<>();
        ObjectMapper mapper = (ObjectMapper) p.getCodec();

        node.fields().forEachRemaining(entry -> {
            String key = entry.getKey();
            JsonNode value = entry.getValue();
            if (value.isArray()) {
                try {
                    List<ISOCodeDetails> details = mapper.readValue(mapper.treeAsTokens(value), new TypeReference<List<ISOCodeDetails>>() {});
                    result.put(key, details);
                } catch (IOException e) {
                    throw new RuntimeException(e);
                }
            } else {
                // Handle the case where the value is not a list (e.g., an empty object)
                result.put(key, new ArrayList<>());
            }
        });

        return result;
    }
}
